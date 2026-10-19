// Polling yang sadar tab tersembunyi dan error
export function createPoller(task, { visible = 3000, hidden = 10000, jitter = 0, maxBackoff = 60000 } = {}) {
    let timer = null;
    let stopped = true;
    let failures = 0;

    const delay = () => {
        const base = document.hidden ? hidden : visible;
        const spread = jitter ? (Math.random() * 2 - 1) * jitter : 0;
        return Math.min(maxBackoff, base * 2 ** failures + spread);
    };

    const schedule = (ms = delay()) => {
        clearTimeout(timer);
        if (!stopped) timer = setTimeout(run, Math.max(0, ms));
    };

    const run = async () => {
        if (stopped) return;

        try {
            const result = await task();
            failures = 0;
            if (result === false) return stop();
        } catch (error) {
            if (error?.name === 'AbortError') return;
            if (error?.status === 401 || error?.status === 419) return stop();
            failures = Math.min(failures + 1, 4);
        }

        schedule();
    };

    const onVisibility = () => {
        if (!document.hidden) schedule(300);
    };

    function stop() {
        stopped = true;
        clearTimeout(timer);
        document.removeEventListener('visibilitychange', onVisibility);
    }

    return {
        start(ms = delay()) {
            if (!stopped) return;
            stopped = false;
            document.addEventListener('visibilitychange', onVisibility);
            schedule(ms);
        },
        stop,
        now: () => schedule(0),
    };
}
