import { BrowserQRCodeReader } from '@zxing/browser';
import Swal from 'sweetalert2';
import { isCurrentCameraAttempt } from './promotion-camera-lifecycle';

const SCAN_DEBOUNCE_MS = 2500;

function readableCameraError(error) {
    if (!window.isSecureContext) {
        return 'Die Kamera ist nur ueber HTTPS verfuegbar. Nutzen Sie alternativ die manuelle Teilnahme-ID.';
    }

    if (error?.name === 'NotAllowedError') {
        return 'Der Kamerazugriff wurde abgelehnt. Bitte erlauben Sie die Kamera oder nutzen Sie die manuelle Eingabe.';
    }

    if (error?.name === 'NotFoundError') {
        return 'Auf diesem Geraet wurde keine Kamera gefunden.';
    }

    return 'Die Kamera konnte nicht gestartet werden. Bitte nutzen Sie die manuelle Teilnahme-ID.';
}

export default function promotionScanner(wire) {
    return {
        open: false,
        phase: 'camera',
        busy: false,
        alertOpen: false,
        cameraStarting: false,
        cameraGeneration: 0,
        cameraError: '',
        manualParticipationId: '',
        participant: null,
        turnId: null,
        reader: null,
        controls: null,
        lastPayload: '',
        lastPayloadAt: 0,
        beforeUnloadHandler: null,
        navigatingHandler: null,

        init() {
            this.$watch('open', (value) => {
                document.documentElement.classList.toggle('overflow-hidden', value);

                if (value && this.phase === 'camera') {
                    this.$nextTick(() => this.startCamera());
                } else if (!value) {
                    this.stopCamera();
                }
            });

            this.beforeUnloadHandler = () => this.stopCamera();
            this.navigatingHandler = () => this.stopCamera();
            window.addEventListener('beforeunload', this.beforeUnloadHandler);
            document.addEventListener('livewire:navigating', this.navigatingHandler);
        },

        destroy() {
            this.stopCamera();

            if (this.beforeUnloadHandler) {
                window.removeEventListener('beforeunload', this.beforeUnloadHandler);
            }

            if (this.navigatingHandler) {
                document.removeEventListener('livewire:navigating', this.navigatingHandler);
            }

            document.documentElement.classList.remove('overflow-hidden');
        },

        async show() {
            this.resetForNextScan();
            this.open = true;
            this.$nextTick(() => this.$refs.closeButton?.focus());
        },

        resume(payload) {
            this.resetForNextScan();
            this.turnId = payload.turn_id;
            this.participant = payload.participant;
            this.phase = 'result';
            this.open = true;
            this.$nextTick(() => this.$refs.closeButton?.focus());
        },

        async close() {
            if (this.busy || this.alertOpen) {
                return;
            }

            if (this.turnId) {
                const confirmed = await this.alert({
                    title: 'Aktiven Aufruf abbrechen?',
                    text: 'Das Ticket wird wieder freigegeben und der Teilnehmer kann spaeter erneut gescannt werden.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Ja, freigeben',
                    cancelButtonText: 'Weiter beobachten',
                    confirmButtonColor: '#0f766e',
                });

                if (!confirmed.isConfirmed) {
                    return;
                }

                await this.releaseTurn();
            }

            this.open = false;
            this.resetForNextScan();
        },

        async startCamera() {
            if (!this.open || this.phase !== 'camera' || this.cameraStarting || this.controls) {
                return;
            }

            const generation = ++this.cameraGeneration;
            let returnedControls = null;
            this.cameraStarting = true;
            this.cameraError = '';

            try {
                if (!navigator.mediaDevices?.getUserMedia) {
                    throw new DOMException('MediaDevices unavailable', 'NotSupportedError');
                }

                this.reader ??= new BrowserQRCodeReader(undefined, {
                    delayBetweenScanAttempts: 180,
                    delayBetweenScanSuccess: 650,
                });

                const video = this.$refs.video;
                returnedControls = await this.reader.decodeFromConstraints({
                    audio: false,
                    video: {
                        facingMode: { ideal: 'environment' },
                        width: { ideal: 1280 },
                        height: { ideal: 720 },
                    },
                }, video, (result) => {
                    if (result && isCurrentCameraAttempt(this, generation)) {
                        void this.consumeScan(result.getText());
                    }
                });

                if (!isCurrentCameraAttempt(this, generation)) {
                    returnedControls?.stop();
                    this.stopVideoTracks();

                    return;
                }

                this.controls = returnedControls;
            } catch (error) {
                returnedControls?.stop();
                this.stopVideoTracks();

                if (isCurrentCameraAttempt(this, generation)) {
                    this.cameraError = readableCameraError(error);
                }
            } finally {
                this.cameraStarting = false;

                if (this.open && this.phase === 'camera' && !this.controls && generation !== this.cameraGeneration) {
                    this.$nextTick(() => this.startCamera());
                }
            }
        },

        stopCamera() {
            this.cameraGeneration += 1;

            try {
                this.controls?.stop();
            } catch (_) {
                // The stream may already have been stopped by the browser.
            }

            this.controls = null;

            this.stopVideoTracks();
        },

        stopVideoTracks() {
            const video = this.$refs?.video;
            const stream = video?.srcObject;
            if (typeof MediaStream !== 'undefined' && stream instanceof MediaStream) {
                stream.getTracks().forEach((track) => track.stop());
            }

            if (video) {
                video.srcObject = null;
            }
        },

        async submitManual() {
            const value = this.manualParticipationId.trim();
            if (!value) {
                this.cameraError = 'Bitte geben Sie die Teilnahme-ID ein.';
                return;
            }

            await this.consumeScan(value);
        },

        async consumeScan(payload) {
            const value = String(payload ?? '').trim();
            const now = Date.now();

            if (!this.open || !value || this.busy || this.phase !== 'camera') {
                return;
            }

            if (value === this.lastPayload && now - this.lastPayloadAt < SCAN_DEBOUNCE_MS) {
                return;
            }

            this.lastPayload = value;
            this.lastPayloadAt = now;
            this.busy = true;
            this.cameraError = '';

            try {
                const response = await wire.scanTicket(value);
                if (!response?.ok) {
                    this.cameraError = response?.message || 'Dieses Ticket konnte nicht aktiviert werden.';
                    return;
                }

                this.stopCamera();
                this.turnId = response.turn_id;
                this.participant = response.participant;
                this.phase = 'result';

                await this.alert({
                    title: 'Scan erfolgreich',
                    text: `${response.participant.ticket_id}: Der Teilnehmer darf jetzt drehen.`,
                    icon: 'success',
                    timer: 1400,
                    showConfirmButton: false,
                });
            } catch (error) {
                this.cameraError = error?.message || 'Dieses Ticket konnte nicht aktiviert werden.';
            } finally {
                this.busy = false;
            }
        },

        async record(fieldId) {
            if (!this.turnId || this.busy) {
                return;
            }

            this.busy = true;

            try {
                const response = await wire.recordResult(this.turnId, fieldId);
                if (!response?.ok) {
                    throw new Error(response?.message || 'Das Ergebnis konnte nicht gespeichert werden.');
                }

                if (!response.final) {
                    this.participant = { ...this.participant, instruction: response.instruction };
                    await this.alert({
                        title: response.title,
                        text: response.message,
                        icon: 'info',
                        confirmButtonText: 'Weiterdrehen',
                        confirmButtonColor: '#0f766e',
                    });
                    return;
                }

                await this.alert({
                    title: 'Ergebnis gespeichert',
                    text: response.message,
                    icon: 'success',
                    confirmButtonText: response.scan_next === false ? 'Konsole schliessen' : 'Naechsten Teilnehmer scannen',
                    confirmButtonColor: '#0f766e',
                });

                if (response.scan_next === false) {
                    this.open = false;
                    this.resetForNextScan();

                    return;
                }

                this.resetForNextScan();
                this.$nextTick(() => this.startCamera());
            } catch (error) {
                await this.alert({
                    title: 'Speichern nicht moeglich',
                    text: error?.message || 'Das Ergebnis konnte nicht gespeichert werden.',
                    icon: 'error',
                    confirmButtonText: 'Erneut versuchen',
                });
            } finally {
                this.busy = false;
            }
        },

        async releaseTurn() {
            if (!this.turnId || this.busy) {
                return;
            }

            this.busy = true;

            try {
                const response = await wire.releaseTurn(this.turnId);
                if (!response?.ok) {
                    throw new Error(response?.message || 'Der aktive Aufruf konnte nicht freigegeben werden.');
                }
            } catch (error) {
                await this.alert({
                    title: 'Freigabe fehlgeschlagen',
                    text: error?.message || 'Der aktive Aufruf konnte nicht freigegeben werden.',
                    icon: 'error',
                });
                throw error;
            } finally {
                this.busy = false;
            }
        },

        resetForNextScan() {
            this.stopCamera();
            this.phase = 'camera';
            this.cameraError = '';
            this.manualParticipationId = '';
            this.participant = null;
            this.turnId = null;
            this.lastPayload = '';
            this.lastPayloadAt = 0;
        },

        alert(options) {
            this.alertOpen = true;

            return Swal.fire({
                ...options,
                target: this.$refs.dialog,
            }).finally(() => {
                this.alertOpen = false;
            });
        },
    };
}
