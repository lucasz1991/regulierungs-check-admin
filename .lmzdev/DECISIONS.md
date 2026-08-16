# Decisions

Record durable decisions with date, context, decision, and consequences.

## 2026-08-16 | Social Login ist ein eigener Admin-Einstellungsbereich

- Google- und Apple-Konfiguration wird im Admin nicht innerhalb der Promotion-Einstellungen dargestellt, sondern in einem eigenen Tab `Social Login`.
- Promotion und Social Login behalten ihre bestehenden getrennten Livewire-Komponenten und Sicherheitsregeln; Datenhaltung und OAuth-Ablauf ändern sich nicht.
- Social-Provider-Speicheraktionen führen nach Erfolg zurück zu `#social-login`; Validierungsfehler öffnen denselben Bereich wieder.

## 2026-08-13 | Promotion laeuft ausschliesslich in direkten Webrequests

- Gewinn-QR, Bindung, Teilnehmerbestaetigung, Ausgabe und Korrektur bleiben transaktionale Webaktionen; es gibt keine Promotion-Commands, Jobs oder Scheduler-Eintraege.
- Die HMAC-Auditkette wird synchron in derselben Fachtransaktion geschrieben und verifiziert. Externe Auditmails, Ankerstatus und getrennte IP-/User-Agent-Zugriffskontexte entfallen.
- Admin-Einstellungen enthalten nur Aktivierung, Einloese-Basis-URL und QR-Gueltigkeit; Auditschluessel und Konfigurations-MAC bleiben intern erzeugt und verschluesselt.

## 2026-08-13 | Promotion arbeitet ausschliesslich durch direkte Webaktionen

- Der Volladmin pflegt nur Freigabe, oeffentliche Einloeseadresse und QR-Gueltigkeit; der HMAC-Schluessel wird intern automatisch erzeugt.
- Einladung und Hochstufung erstellen beziehungsweise haerten das Promotion-Team atomar in derselben Webaktion. Ein vorbereitender Command ist nicht erforderlich.
- Gewinnanlage, Scan/Verknuepfung und synchrones Audit benoetigen weder Promotion-Commands noch Queue-Jobs oder Scheduler.

## 2026-08-13 | Auditverankerung ist kein Feature-Toggle

- `enabled=false` verhindert neue Promotionvorgaenge, aber nicht Verifikation und externe Verankerung historischer Events.
- Der Command verwendet `PromotionAuditChain` direkt, weil fachliche Win-Mutationen weiterhin korrekt am Aktivschalter haengen.
- Korrupter Settings-MAC oder ungueltige Kontrolladresse endet mit Failure ohne E-Mail; auch bereits verankerte Koepfe werden geprueft.

## 2026-08-05 | News social-image CTA removal

- Removed the complete graphical article CTA instead of hiding only its text.
- Re-anchored the remaining text content to the existing bottom margin so the released space is used cleanly in every format.
- Increased `NewsSocialImageController::LAYOUT_VERSION` to `4` so cached legacy images cannot retain the old CTA.

## 2026-08-06 | Preserve the removed CTA footprint

- Supersedes the previous decision to use the released CTA space for text.
- Keeps the former 108 px button height completely empty in addition to the existing 96 px bottom margin.
- Increased the layout version to `5` so cached version-4 images are regenerated with the corrected spacing.

## 2026-08-07 | Persist per-News, per-format social-image layouts

- Store a complete normalized configuration under nullable `posts.social_image_settings`; existing News without JSON keep the previous layout exactly.
- Keep Story, Post, and Link settings independent, while the logo color remains a download-time variant as before.
- Expose only finite dropdown values and validate them server-side with German messages, preventing malformed JSON from breaking the renderer.
- Require an explicit save before preview/download refresh; while settings are dirty, the download action is disabled so the file always matches the visible saved state.
- Use the shared Base project for the database migration and cast the JSON column in both Post models.
- Persist only the active format on each save, preserve unsaved edits in the other formats, and use a per-format preview revision plus per-format cache fingerprint.
- Do not change the News `updated_at` timestamp for social-image layout metadata, otherwise saving one format would invalidate all three image caches.

## 2026-08-07 | Inline auto-saving social-image controls

- Supersedes the explicit-save decision: every allowlisted select change is persisted immediately and increments only the active format's preview revision.
- Supersedes the download-only logo-variant decision: `logo_variant` is now stored independently inside every format's JSON configuration.
- Anchor controls to the visual element they affect; keep side spacing once at the left edge and bottom spacing once at the lower image edge.
- Open every control by hover, keyboard focus, or tap, with the mobile side marker moved inside the image so its complete 36 px target remains available.
- Execute the previously pending nullable JSON migration after runtime schema evidence proved the production-like local database did not yet contain the column.

## 2026-08-13 | Global-admin target guard for user status changes

- Centralize every user activation/deactivation from `Users` and `UserProfile` in one transaction service instead of maintaining divergent component checks.
- Lock the complete global-admin set before actor and target rows so concurrent requests cannot both pass the last-active-admin check.
- Treat every Livewire target ID as untrusted, reject a delegated action atomically when any target is a global admin, and lock the profile target property against snapshot tampering.

## 2026-08-13 | Fail-closed account deletion and WebPage executable fields

- Disable Jetstream account deletion in the Admin UI and keep a server-side 403 guard because Jetstream still registers its Livewire component when the feature is hidden.
- Treat WebPage CSS, JavaScript and free custom meta as privileged executable head configuration available only to a global admin; delegated editors neither receive nor persist these properties.
- Sanitize WebPage SVG on preview, storage and public legacy rendering, and reject HTML in public page titles.

## 2026-08-13 | Blog Rich-HTML bleibt formatiert, aber passiv

- Die Toast-UI-Formatierung bleibt ueber eine enge DOM-Allowlist erhalten: Ueberschriften, Textauszeichnung, Zitate, Listen samt deaktivierten Aufgaben-Checkboxen, Tabellen und Links.
- Bilder, Styles, beliebige Klassen, Formulare sowie aktive/einbettende Elemente werden entfernt; Links erlauben nur interne Ziele sowie HTTP(S), Mail und Telefon, externe HTTP(S)-Links erhalten `noopener noreferrer`.
- Admin und Base verwenden byte-identischen Sanitizer-Code, damit Speicherung und Ausgabe nicht auseinanderlaufen.

## 2026-08-14 | E-Mail-Verifikation ist im Adminsystem optional

- Admin- und Promotion-Mitarbeiterzugang erfordern Anmeldung, aktiven Kontostatus und die jeweiligen RBAC-Rechte, aber keinen gesetzten `email_verified_at`-Zeitstempel.
- Fortify-Verifikationsrouten, die gebrandete Verify-Seite und der Mailversand bleiben als freiwillige Funktion bestehen.
- Die getrennte Teilnehmerpruefung vor einer Gewinnausgabe bleibt im Promotion-Domainservice erhalten; sie ist kein Zugangsgate fuer Admins oder Mitarbeiter.

## 2026-08-16 | Admin nutzt denselben gespiegelten Glücksrad-V2-Domainvertrag wie Base

- Ticket-, Aufruf-, Ergebnis-, Audit-, QR-, Settings-, Social-Settings- und Mailstatus-Services bleiben zwischen Base und Admin byte-identisch; nur Base besitzt die additive Migration.
- Neue Scans verlangen eine aktuell öffentliche Kampagne sowie ein aktives, E-Mail-verifiziertes Teilnehmerkonto. Bereits aktive Aufrufe können nach Kampagnenwechsel oder Deaktivierung weiterhin sicher abgeschlossen oder freigegeben werden.
- Persönliche QR-Codes sind stateless und werden nicht gespeichert. Participation-, Ticket-, Aufruf-, Ergebnis-, Mail- und Kampagnenzustände sind synchron HMAC-gebunden und werden vor jeder Mutation fail-closed geprüft.
- Promotion läuft ausschließlich über direkte Webaktionen ohne Commands, Jobs, Scheduler, Queue-Worker oder ENV-Konfiguration.
