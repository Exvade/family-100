document.addEventListener('DOMContentLoaded', () => {
    let isSpinning = false;
    let soundEnabled = true;

    // Ambil data langsung dari Controller Laravel ($participants), fallback ke dummy jika belum ada
    const defaultDummyGuests = [
        'Budi Santoso', 'Rina Marlina', 'Dimas Pratama', 'Siti Aisyah', 'Rizky Firmansyah',
        'Andi Wijaya', 'Citra Dewi', 'Fajar Hidayat', 'Dewi Lestari', 'Eko Prasetyo',
        'Hendra Gunawan', 'Maya Indah', 'Bayu Saputra', 'Nurul Hidayah', 'Agus Setiawan',
        'Mega Wulandari', 'Reza Pahlevi', 'Taufik Rahman', 'Indah Permata', 'Yusuf Maulana',
        'Anisa Rahmawati', 'Bambang Tri', 'Dian Sastrowardoyo', 'Guruh Soekarno', 'Wulan Guritno'
    ];

    let participantPool = (Array.isArray(window.doorprizeParticipants) && window.doorprizeParticipants.length > 0)
        ? window.doorprizeParticipants
        : defaultDummyGuests;

    const stackEl = document.getElementById('barsVerticalStack');
    let currentSlots = Number(stackEl?.dataset.slots) || (window.doorprizeSpin?.slots) || 5;

    let activeWinners = Array(currentSlots).fill(null);
    let rollers = Array(currentSlots).fill(null);

    const popupCelebrationModal = document.getElementById('popupCelebrationModal');
    const popupWinnersGrid = document.getElementById('popupWinnersGrid');
    const btnCloseCelebrationModal = document.getElementById('btnCloseCelebrationModal');

    // 1. Render Static Marquee Bulbs
    const bulbsTrack = document.getElementById('marqueeBulbsTrack');
    function drawMarqueeBulbs() {
        if (!bulbsTrack) return;
        bulbsTrack.innerHTML = '';
        const rect = bulbsTrack.getBoundingClientRect();
        const w = rect.width;
        const h = rect.height;
        const gap = 44;

        for (let x = 24; x <= w - 24; x += gap) {
            insertBulb(x, -6);
            insertBulb(x, h - 7);
        }
        for (let y = 24; y <= h - 24; y += gap) {
            insertBulb(-6, y);
            insertBulb(w - 7, y);
        }
    }
    function insertBulb(x, y) {
        const b = document.createElement('div');
        b.className = 'static-bulb';
        b.style.left = `${x}px`;
        b.style.top = `${y}px`;
        bulbsTrack.appendChild(b);
    }
    drawMarqueeBulbs();
    window.addEventListener('resize', drawMarqueeBulbs);

    // 2. Procedural Web Audio API
    let audioCtx = null;
    function getAudioContext() {
        if (!audioCtx) {
            const AudioContext = window.AudioContext || window.webkitAudioContext;
            audioCtx = new AudioContext();
        }
        if (audioCtx.state === 'suspended') {
            audioCtx.resume();
        }
        return audioCtx;
    }

    let lastTickTime = 0;

    function playTick() {
        if (!soundEnabled) return;
        const now = performance.now();
        if (now - lastTickTime < 75) return;
        lastTickTime = now;

        try {
            const ctx = getAudioContext();
            const t = ctx.currentTime;
            const osc = ctx.createOscillator();
            const gain = ctx.createGain();

            osc.type = 'sine';
            osc.frequency.setValueAtTime(700, t);
            osc.frequency.exponentialRampToValueAtTime(160, t + 0.028);

            gain.gain.setValueAtTime(0.045, t);
            gain.gain.exponentialRampToValueAtTime(0.0001, t + 0.028);

            osc.connect(gain);
            gain.connect(ctx.destination);
            osc.start(t);
            osc.stop(t + 0.03);
        } catch (e) {}
    }

    function playChime(step = 0) {
        if (!soundEnabled) return;
        try {
            const ctx = getAudioContext();
            const t = ctx.currentTime;
            const freqs = [523.25, 659.25, 783.99, 880.00, 1046.50, 1174.66, 1318.51];
            const f = freqs[step % freqs.length];

            const osc = ctx.createOscillator();
            const gain = ctx.createGain();
            osc.type = 'sine';
            osc.frequency.setValueAtTime(f, t);

            gain.gain.setValueAtTime(0.12, t);
            gain.gain.exponentialRampToValueAtTime(0.0001, t + 0.45);

            osc.connect(gain);
            gain.connect(ctx.destination);
            osc.start(t);
            osc.stop(t + 0.45);
        } catch (e) {}
    }

    function playFanfare() {
        if (!soundEnabled) return;
        try {
            const ctx = getAudioContext();
            const notes = [523.25, 659.25, 783.99, 1046.50, 1318.51];
            notes.forEach((freq, idx) => {
                setTimeout(() => {
                    const t = ctx.currentTime;
                    const osc = ctx.createOscillator();
                    const gain = ctx.createGain();
                    osc.type = 'sine';
                    osc.frequency.setValueAtTime(freq, t);
                    gain.gain.setValueAtTime(0.11, t);
                    gain.gain.exponentialRampToValueAtTime(0.0001, t + 0.8);
                    osc.connect(gain);
                    gain.connect(ctx.destination);
                    osc.start(t);
                    osc.stop(t + 0.8);
                }, idx * 120);
            });
        } catch (e) {}
    }

    function toggleSound() {
        soundEnabled = !soundEnabled;
        getAudioContext();
        if (soundEnabled) {
            playChime(0);
        }
    }

    window.addEventListener('click', () => getAudioContext(), { once: true });
    window.addEventListener('keydown', () => getAudioContext(), { once: true });

    // 3. Dynamic Slot Bars Management (1 to 10 winners)
    function attachBarListeners(bar) {
        bar.addEventListener('click', () => {
            if (isSpinning || popupCelebrationModal.classList.contains('active')) return;
            const idx = parseInt(bar.dataset.index, 10);
            const alreadyWon = activeWinners.filter(w => w !== null);
            const chosen = pickRandomCandidate(alreadyWon);
            spinSlotBar(idx, chosen);
        });
    }

    function ensureBars(count) {
        count = Math.max(1, Math.min(10, count));
        const stack = document.getElementById('barsVerticalStack');
        if (!stack) return;

        const currentBarCount = stack.querySelectorAll('.family-participant-bar').length;
        if (currentSlots === count && currentBarCount === count) {
            return;
        }

        currentSlots = count;
        stack.dataset.slots = count;
        stack.classList.toggle('single-winner', count === 1);
        stack.classList.toggle('two-columns', count > 5);
        stack.innerHTML = '';

        rollers.forEach(t => clearInterval(t));
        rollers = Array(count).fill(null);
        activeWinners = Array(count).fill(null);

        for (let i = 0; i < count; i++) {
            const bar = document.createElement('div');
            bar.className = 'family-participant-bar';
            bar.id = `participantBar-${i}`;
            bar.dataset.index = i;
            const shortcutHint = i < 9 ? `Shortcut: Angka ${i + 1}` : (i === 9 ? 'Shortcut: Angka 0' : '');
            bar.title = `Klik untuk putar baris ini ${shortcutHint ? '(' + shortcutHint + ')' : ''}`;
            bar.innerHTML = `
                <div class="sphere-rank-badge">${i + 1}</div>
                <div class="bar-label-container">
                    <div class="bar-rank-heading">PEMENANG #${i + 1}</div>
                    <div class="bar-status-sub" id="barStatusSub-${i}">Siap Diundi</div>
                </div>
                <div class="bar-participant-window">
                    <div class="slot-dots-unrevealed" id="dotsDisplay-${i}">••••••••••••••</div>
                    <div class="participant-name-text" id="nameDisplay-${i}" style="display: none;">-</div>
                </div>
            `;
            attachBarListeners(bar);
            stack.appendChild(bar);
        }
    }

    // Attach listeners to initial bars if already rendered by Blade
    document.querySelectorAll('.family-participant-bar').forEach(attachBarListeners);

    function pickRandomCandidate(excluded = []) {
        const available = participantPool.filter(name => !excluded.includes(name));
        if (available.length > 0) {
            return available[Math.floor(Math.random() * available.length)];
        }
        return participantPool[Math.floor(Math.random() * participantPool.length)] || 'Tamu Undangan';
    }

    function barParts(index) {
        return {
            bar: document.getElementById(`participantBar-${index}`),
            dots: document.getElementById(`dotsDisplay-${index}`),
            nameDisplay: document.getElementById(`nameDisplay-${index}`),
            statusSub: document.getElementById(`barStatusSub-${index}`),
        };
    }

    function stopRolling(index) {
        clearInterval(rollers[index]);
        rollers[index] = null;
    }

    function showRolling(index) {
        const { bar, dots, nameDisplay, statusSub } = barParts(index);
        if (!bar) return;
        bar.classList.remove('revealed');
        if (dots) dots.style.display = 'none';
        if (nameDisplay) nameDisplay.style.display = 'block';
        if (statusSub) {
            statusSub.textContent = 'Mengundi Nama...';
            statusSub.style.color = '#fde68a';
        }
    }

    function startRolling(index) {
        stopRolling(index);
        showRolling(index);
        const { nameDisplay } = barParts(index);
        if (!nameDisplay) return;
        rollers[index] = setInterval(() => {
            nameDisplay.textContent = participantPool[Math.floor(Math.random() * participantPool.length)];
            playTick();
        }, 65);
    }

    function revealBar(index, winner, { category = null, celebrate = true } = {}) {
        const { bar, dots, nameDisplay, statusSub } = barParts(index);
        if (!bar) return;
        const winnerName = typeof winner === 'object' && winner !== null ? winner.name : winner;
        const winnerCat = typeof winner === 'object' && winner !== null ? winner.category : category;

        activeWinners[index] = winnerName;
        if (dots) dots.style.display = 'none';
        if (nameDisplay) {
            nameDisplay.style.display = 'block';
            nameDisplay.textContent = winnerName;
        }
        bar.classList.add('revealed');
        if (statusSub) {
            statusSub.textContent = winnerCat ? `Pemenang • ${winnerCat}` : 'Pemenang Terpilih';
            statusSub.style.color = '#55efc4';
        }

        if (!celebrate) return;
        playChime(index);

        try {
            const rect = bar.getBoundingClientRect();
            confetti({
                particleCount: 30,
                spread: 55,
                origin: {
                    x: (rect.left + rect.width / 2) / window.innerWidth,
                    y: (rect.top + rect.height / 2) / window.innerHeight
                }
            });
        } catch (e) {}
    }

    function resetBar(index) {
        const { bar, dots, nameDisplay, statusSub } = barParts(index);
        if (!bar) return;
        stopRolling(index);
        bar.classList.remove('revealed');
        if (dots) dots.style.display = 'block';
        if (nameDisplay) nameDisplay.style.display = 'none';
        if (statusSub) {
            statusSub.textContent = 'Siap Diundi';
            statusSub.style.color = '#93c5fd';
        }
        activeWinners[index] = null;
    }

    function spinSlotBar(index, chosenName, onComplete) {
        const { nameDisplay } = barParts(index);
        if (!nameDisplay) return;

        stopRolling(index);
        showRolling(index);

        let rolls = 0;
        const maxRolls = 18 + index * 4;
        const interval = setInterval(() => {
            const randomName = participantPool[Math.floor(Math.random() * participantPool.length)];
            nameDisplay.textContent = randomName;
            playTick();
            rolls++;

            if (rolls >= maxRolls) {
                stopRolling(index);
                const winner = chosenName || pickRandomCandidate();
                revealBar(index, winner);

                if (onComplete) {
                    onComplete(winner);
                } else {
                    const allRevealed = activeWinners.slice(0, currentSlots).every(w => w !== null);
                    if (allRevealed && !isSpinning) {
                        setTimeout(() => {
                            playFanfare();
                        }, 250);
                        triggerCelebrationPopup([...activeWinners.slice(0, currentSlots)]);
                    }
                }
            }
        }, 65);
        rollers[index] = interval;
    }

    // 4. Spin All Bars Sequentially
    function spinAllBars() {
        if (isSpinning) return;
        isSpinning = true;

        const selectedWinners = [];
        const poolCopy = [...participantPool];
        for (let i = poolCopy.length - 1; i > 0; i--) {
            const j = Math.floor(Math.random() * (i + 1));
            [poolCopy[i], poolCopy[j]] = [poolCopy[j], poolCopy[i]];
        }

        for (let i = 0; i < currentSlots; i++) {
            selectedWinners.push(poolCopy[i] || `Pemenang #${i + 1}`);
        }

        let finished = 0;
        selectedWinners.forEach((winnerName, idx) => {
            setTimeout(() => {
                spinSlotBar(idx, winnerName, () => {
                    finished++;
                    if (finished === currentSlots) {
                        isSpinning = false;
                        setTimeout(() => {
                            playFanfare();
                        }, 250);
                        triggerCelebrationPopup(selectedWinners);
                    }
                });
            }, idx * 300);
        });
    }

    // 5. Celebration Modal Popup
    function triggerCelebrationPopup(winners) {
        try {
            const duration = 3500;
            const end = Date.now() + duration;
            const colors = ['#f59e0b', '#fffef0', '#3b82f6', '#f43f5e', '#10b981'];

            (function frame() {
                confetti({ particleCount: 8, angle: 60, spread: 55, origin: { x: 0, y: 0.7 }, colors: colors });
                confetti({ particleCount: 8, angle: 120, spread: 55, origin: { x: 1, y: 0.7 }, colors: colors });
                if (Date.now() < end) {
                    requestAnimationFrame(frame);
                }
            }());
        } catch (e) {}

        popupWinnersGrid.innerHTML = '';
        if (winners && winners.length > 0) {
            winners.forEach((w, idx) => {
                const name = typeof w === 'object' && w !== null ? w.name : w;
                const cat = typeof w === 'object' && w !== null && w.category ? w.category : 'TAMU TERPILIH';
                const card = document.createElement('div');
                card.className = 'winner-card-cell';
                card.innerHTML = `
                    <div class="winner-card-rank">PEMENANG #${idx + 1}</div>
                    <div class="winner-card-name">${escapeHtml(name)}</div>
                    <div class="winner-card-tag">${escapeHtml(cat)}</div>
                `;
                popupWinnersGrid.appendChild(card);
            });
        }

        setTimeout(() => {
            popupCelebrationModal.classList.add('active');
        }, 800);
    }

    function escapeHtml(text) {
        if (!text) return '';
        return text.replace(/[&<>"']/g, function (m) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[m];
        });
    }

    btnCloseCelebrationModal.addEventListener('click', (e) => {
        e.stopPropagation();
        popupCelebrationModal.classList.remove('active');
    });

    popupCelebrationModal.addEventListener('click', (e) => {
        if (e.target === popupCelebrationModal) {
            popupCelebrationModal.classList.remove('active');
        }
    });

    // 6. Keyboard Shortcuts
    window.addEventListener('keydown', (e) => {
        if (e.code === 'Space' || e.code === 'Enter') {
            if (!isSpinning && !popupCelebrationModal.classList.contains('active')) {
                e.preventDefault();
                spinAllBars();
            }
        } else if (['1', '2', '3', '4', '5', '6', '7', '8', '9', '0'].includes(e.key)) {
            if (!isSpinning && !popupCelebrationModal.classList.contains('active')) {
                const num = e.key === '0' ? 10 : parseInt(e.key, 10);
                const idx = num - 1;
                if (idx < currentSlots) {
                    e.preventDefault();
                    const alreadyWon = activeWinners.filter(w => w !== null);
                    const chosen = pickRandomCandidate(alreadyWon);
                    spinSlotBar(idx, chosen);
                }
            }
        } else if (e.key === 'f' || e.key === 'F') {
            if (!document.fullscreenElement) {
                document.documentElement.requestFullscreen().catch(() => {});
            } else {
                document.exitFullscreen().catch(() => {});
            }
        } else if (e.key === 'm' || e.key === 'M') {
            toggleSound();
        } else if (e.key === 'r' || e.key === 'R') {
            if (isSpinning) return;
            for (let idx = 0; idx < currentSlots; idx++) {
                resetBar(idx);
            }
        } else if (e.key === 'c' || e.key === 'C') {
            if (!isSpinning && activeWinners.slice(0, currentSlots).every(w => w !== null)) {
                triggerCelebrationPopup([...activeWinners.slice(0, currentSlots)]);
            }
        } else if (e.key === 'Escape') {
            popupCelebrationModal.classList.remove('active');
        }
    });

    // 7. Kendali jarak jauh dari dasbor /doorprize
    const remote = window.doorprizeSpin || null;
    let remoteSeq = remote ? remote.seq : 0;

    function remoteStart() {
        popupCelebrationModal.classList.remove('active');
        isSpinning = true;
        for (let idx = 0; idx < currentSlots; idx++) startRolling(idx);
    }

    function remoteStop(winners, { winnerDetails = null, instant = false } = {}) {
        popupCelebrationModal.classList.remove('active');
        isSpinning = true;

        if (winners.length !== currentSlots) {
            ensureBars(winners.length);
        }

        const details = Array.isArray(winnerDetails) && winnerDetails.length === winners.length
            ? winnerDetails
            : winners.map((w) => (typeof w === 'object' && w !== null ? w : { name: w, category: null }));

        winners.forEach((winner, idx) => {
            const detail = details[idx];
            setTimeout(() => {
                stopRolling(idx);
                revealBar(idx, detail?.name || winner, { category: detail?.category, celebrate: !instant });
                if (idx === winners.length - 1) {
                    isSpinning = false;
                    if (!instant) {
                        setTimeout(playFanfare, 250);
                        triggerCelebrationPopup(details);
                    }
                }
            }, instant ? 0 : idx * 300);
        });
    }

    function applyRemote(state, { initial = false } = {}) {
        if (state.slots && state.slots !== currentSlots) {
            ensureBars(state.slots);
        }

        if (Array.isArray(state.pool) && state.pool.length > 0) {
            participantPool = state.pool;
        }

        if (state.status === 'spinning') {
            remoteStart();
        } else if (state.status === 'stopped' && Array.isArray(state.winners) && state.winners.length > 0) {
            remoteStop(state.winners, { winnerDetails: state.winner_details, instant: initial });
        } else if (state.status === 'idle' && !initial) {
            popupCelebrationModal.classList.remove('active');
            for (let idx = 0; idx < currentSlots; idx++) resetBar(idx);
            isSpinning = false;
        }
    }

    if (remote) {
        applyRemote(remote, { initial: true });

        const stateUrl = window.doorprizeSpinStateUrl;
        setInterval(async () => {
            try {
                const response = await fetch(`${stateUrl}?seq=${remoteSeq}`, { headers: { 'Accept': 'application/json' } });
                const state = await response.json();
                if (state.seq !== remoteSeq) {
                    remoteSeq = state.seq;
                    applyRemote(state);
                }
            } catch (e) {}
        }, 1000);
    }
});
