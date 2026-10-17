<script>
    (() => {
        const url = @js(route('challenges.status'));
        let base = null;

        // Pindah ke draft saat tantangan diterima
        const poll = async () => {
            try {
                const res = await fetch(url, { headers: { Accept: 'application/json' } });
                const data = await res.json();

                if (base === null) {
                    base = data;
                } else if (data.duel && data.duel !== base.duel) {
                    return window.location.assign(data.redirect);
                } else if (data.inbox !== base.inbox || data.sent !== base.sent) {
                    return window.location.reload();
                }
            } catch {}
            setTimeout(poll, 3000);
        };
        poll();
    })();
</script>
