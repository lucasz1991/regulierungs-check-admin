export function isCurrentCameraAttempt(state, generation) {
    return state.open
        && state.phase === 'camera'
        && state.cameraGeneration === generation;
}
