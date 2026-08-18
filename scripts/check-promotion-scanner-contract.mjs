import fs from 'node:fs';
import path from 'node:path';
import { isCurrentCameraAttempt } from '../resources/js/promotion-camera-lifecycle.js';

const file = path.resolve('resources/js/promotion-scanner.js');
const source = fs.readFileSync(file, 'utf8');
const viewSource = fs.readFileSync(path.resolve('resources/views/livewire/promotion/promotion-console.blade.php'), 'utf8');
const administrationViewSource = fs.readFileSync(path.resolve('resources/views/livewire/admin/promotion-administration.blade.php'), 'utf8');
const contracts = [
    ['lokales ZXing', "from '@zxing/browser'"],
    ['bevorzugte Rückkamera', "facingMode: { ideal: 'environment' }"],
    ['Decode-Debounce', 'SCAN_DEBOUNCE_MS'],
    ['serverseitiger Scan', 'wire.scanTicket(value)'],
    ['serverseitiges Ergebnis', 'wire.recordResult(this.turnId, fieldId)'],
    ['serverseitiger Abbruch', 'wire.releaseTurn(this.turnId)'],
    ['Response-Prüfung', 'if (!response?.ok)'],
    ['MediaTrack-Cleanup', 'stream.getTracks().forEach((track) => track.stop())'],
    ['Alpine-Teardown', 'destroy()'],
    ['Listener-Cleanup', "removeEventListener('livewire:navigating'"],
    ['Kamera-Generation', 'cameraGeneration'],
    ['veraltete Controls stoppen', 'returnedControls?.stop()'],
    ['geschlossenes Modal ignorieren', 'if (!this.open || !value'],
    ['Lifecycle-Pruefung', 'isCurrentCameraAttempt(this, generation)'],
    ['kein Schliessen waehrend Servermutation', 'if (this.busy || this.alertOpen)'],
    ['kein automatischer Scan bei pausierter Kampagne', 'response.scan_next === false'],
    ['SweetAlert innerhalb des Fokus-Traps', 'target: this.$refs.dialog'],
    ['kein Escape-Reentry waehrend Alert', 'this.busy || this.alertOpen'],
];

const missing = contracts.filter(([, fragment]) => !source.includes(fragment));
if (!viewSource.includes('x-show.important="open"') || !viewSource.includes('x-show.important="phase === \'result\'"')) {
    missing.push(['Tailwind-important-kompatible Scanner-Sichtbarkeit']);
}
if (!viewSource.includes('<template x-teleport="body" wire:ignore>') || !viewSource.includes('wire:ignore\n            x-show.important="open"')) {
    missing.push(['poll-stabiler Teleport-Dialog']);
}
for (const tab of ['overview', 'campaign', 'prizes', 'history']) {
    if (!administrationViewSource.includes(`x-show.important="tab === '${tab}'"`)) {
        missing.push([`Tailwind-important-kompatibler Admin-Tab ${tab}`]);
    }
}
if (missing.length > 0) {
    console.error(`Promotion-Scanner-Vertrag verletzt: ${missing.map(([label]) => label).join(', ')}`);
    process.exit(1);
}

const state = { open: true, phase: 'camera', cameraGeneration: 0 };
const generation = ++state.cameraGeneration;
let stopped = false;
const delayedControls = Promise.resolve().then(() => ({ stop: () => { stopped = true; } }));

state.open = false;
state.cameraGeneration += 1;

const returnedControls = await delayedControls;
if (isCurrentCameraAttempt(state, generation)) {
    throw new Error('Ein geschlossener, veralteter Kamerastart wurde irrtuemlich als aktiv bewertet.');
}
returnedControls.stop();

if (!stopped) {
    throw new Error('Die Controls eines veralteten Kamerastarts wurden nicht gestoppt.');
}

console.log(`Promotion scanner contract OK (${contracts.length} checks).`);
