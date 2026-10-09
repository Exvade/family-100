function initDoorprizeTv() {
    let isSpinning = false;
    let soundEnabled = true;

    // 0. Proportional 16:9 Stage Auto-Scaler & Mobile Responsive Detector
    function fitTvStage() {
        const canvas = document.getElementById('stageCanvas');
        if (!canvas) return;
        const w = window.innerWidth;
        const h = window.innerHeight;
        const isMobile = w < 1024 || (w < 1200 && h > w);

        if (isMobile) {
            document.body.classList.add('mobile-responsive-mode');
            canvas.style.transform = 'none';
        } else {
            document.body.classList.remove('mobile-responsive-mode');
            const scale = Math.min(w / 1600, h / 900);
            canvas.style.transform = `scale(${scale})`;
        }
    }
    window.addEventListener('resize', fitTvStage);
    fitTvStage();

    // 1. Participant Pool dari Controller Laravel
    const defaultDummyGuests = [
        'Rizky Pratama', 'Siti Aulia', 'Dimas Arya', 'Nadya Putri', 'Fauzan Hakim',
        'Bagus Setiawan', 'Putri Anggraini', 'Andi Saputra', 'Nurul Hidayah', 'Arif Rahman',
        'Budi Santoso', 'Rina Marlina', 'Citra Dewi', 'Fajar Hidayat', 'Dewi Lestari',
        'Eko Prasetyo', 'Hendra Gunawan', 'Maya Indah', 'Bayu Saputra', 'Agus Setiawan'
    ];

    let participantPool = (Array.isArray(window.doorprizeParticipants) && window.doorprizeParticipants.length > 0)
        ? window.doorprizeParticipants
        : defaultDummyGuests;

    const idleBarsStack = document.getElementById('idleBarsStack');
    const resultBarsStack = document.getElementById('resultBarsStack');
    const gachaLinesStack = document.getElementById('gachaLinesStack');
    let currentSlots = Number(idleBarsStack?.dataset.slots) || (window.doorprizeSpin?.slots) || 5;

    let activeWinners = Array(currentSlots).fill(null);
    let activeWinnerDetails = Array(currentSlots).fill(null);
    let slotLocked = Array(currentSlots).fill(false);

    let currentPrizes = Array.isArray(window.doorprizePrizes) ? window.doorprizePrizes : (Array.isArray(window.doorprizeSpin?.prizes) ? window.doorprizeSpin.prizes : []);
    let currentPrizePlan = Array.isArray(window.doorprizeSpin?.prize_plan) ? window.doorprizeSpin.prize_plan : [];

    function getEffectiveSlots() {
        if (isSpinning || (activeWinners && activeWinners.some(w => w !== null))) {
            return currentSlots;
        }
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.has('slots')) {
            const parsed = parseInt(urlParams.get('slots'), 10);
            if (parsed >= 1 && parsed <= 10) return parsed;
        }
        try {
            const rawStored = localStorage.getItem('doorprize.allocation') || sessionStorage.getItem('doorprize.allocation');
            if (rawStored) {
                const stored = JSON.parse(rawStored);
                if (Array.isArray(stored) && stored.length > 0) {
                    const valid = stored.filter(r => r && r.prizeId);
                    if (valid.length > 0) {
                        const sum = valid.reduce((acc, r) => acc + (parseInt(r.count, 10) || 0), 0);
                        if (sum >= 1) return Math.min(10, sum);
                    }
                }
            }
            const storedSlots = parseInt(localStorage.getItem('doorprize.slots') || sessionStorage.getItem('doorprize.slots'), 10);
            if (storedSlots && storedSlots >= 1 && storedSlots <= 10) {
                return storedSlots;
            }
        } catch (e) {}
        return currentSlots;
    }

    function getPrizeForSlot(slotIdx, prizes = currentPrizes, plan = currentPrizePlan) {
        // 1. Cek dari prize_plan remote jika ada dan valid
        if (Array.isArray(plan) && plan[slotIdx] !== undefined && plan[slotIdx] !== null) {
            const pId = plan[slotIdx];
            const found = prizes.find(p => String(p.id) === String(pId));
            if (found) return found;
        }

        // 2. Cek apakah ada alokasi dari localStorage (sinkronisasi real-time dengan dasbor operator)
        try {
            const stored = JSON.parse(localStorage.getItem('doorprize.allocation') || '[]');
            if (Array.isArray(stored) && stored.length > 0) {
                const validRows = stored.filter(r => r && r.prizeId);
                if (validRows.length > 0) {
                    const effSlots = getEffectiveSlots();
                    const explicitSum = validRows.reduce((acc, r) => acc + (parseInt(r.count, 10) || 0), 0);
                    const blankRows = validRows.filter(r => !parseInt(r.count, 10));

                    let targetId = null;
                    let slotCounter = 0;

                    for (const row of validRows) {
                        const rowId = row.prizeId;
                        let count = parseInt(row.count, 10);
                        if (!count) {
                            if (blankRows.length === 1 && effSlots > explicitSum) {
                                count = Math.max(1, effSlots - explicitSum);
                            } else {
                                count = 1;
                            }
                        }

                        if (slotIdx >= slotCounter && slotIdx < slotCounter + count) {
                            targetId = rowId;
                            break;
                        }
                        slotCounter += count;
                    }

                    if (!targetId && validRows[0]?.prizeId) {
                        targetId = validRows[0].prizeId;
                    }

                    if (targetId) {
                        const found = prizes.find(p => String(p.id) === String(targetId));
                        if (found) return found;
                    }
                }
            }
        } catch (e) {}

        // 3. Fallback: Hadiah pertama di daftar prizes untuk SEMUA slot agar tidak mengambil kado lain dari DB
        if (Array.isArray(prizes) && prizes.length > 0) {
            return prizes[0];
        }

        return {
            id: slotIdx + 1,
            name: `Hadiah #${slotIdx + 1}`,
            image_url: null,
            quantity: 1
        };
    }

    function renderPrizeShowcase(prizes = currentPrizes, plan = currentPrizePlan, slots = null) {
        currentPrizes = prizes;
        currentPrizePlan = plan;
        const showcaseDeck = document.getElementById('prizeShowcaseDeck');
        if (!showcaseDeck) return;

        const effectiveSlots = (slots !== null && slots !== undefined) ? Number(slots) : getEffectiveSlots();
        if (!isSpinning && (!activeWinners || !activeWinners.some(w => w !== null)) && effectiveSlots !== currentSlots) {
            updateSlotsLayout(effectiveSlots);
        }

        showcaseDeck.dataset.slots = effectiveSlots;

        // Ambil hadiah yang dialokasikan untuk slot yang sedang aktif (effectiveSlots)
        const mapped = [];
        for (let i = 0; i < effectiveSlots; i++) {
            mapped.push(getPrizeForSlot(i, prizes, plan));
        }

        // Kumpulkan hadiah unik untuk slot yang aktif
        const uniquePrizesMap = new Map();
        mapped.forEach((p, idx) => {
            if (p) {
                const key = String(p.id ?? p.name);
                if (!uniquePrizesMap.has(key)) {
                    uniquePrizesMap.set(key, { prize: p, slots: [idx + 1] });
                } else {
                    uniquePrizesMap.get(key).slots.push(idx + 1);
                }
            }
        });

        const uniquePrizes = Array.from(uniquePrizesMap.values());
        const uniqueCount = uniquePrizes.length;

        showcaseDeck.dataset.slots = effectiveSlots;
        showcaseDeck.dataset.unique = uniqueCount;

        const stageCanvas = document.querySelector('.stage-canvas-16-9');
        if (stageCanvas) {
            stageCanvas.dataset.slots = effectiveSlots;
            stageCanvas.dataset.unique = uniqueCount;
        }
        const spotlightZone = document.getElementById('tvPrizeSpotlightZone');
        if (spotlightZone) {
            spotlightZone.dataset.slots = effectiveSlots;
            spotlightZone.dataset.unique = uniqueCount;
        }
        const stageRightZone = document.getElementById('stageRightZone');
        if (stageRightZone) stageRightZone.dataset.slots = effectiveSlots;

        const badgeTitle = document.getElementById('prizeBadgeTitle');
        if (badgeTitle) {
            badgeTitle.textContent = uniqueCount >= 4 
                ? `${uniqueCount} HADIAH YANG DIUNDI` 
                : 'HADIAH YANG DIUNDI';
        }

        const formatSlotRange = (slotsArray) => {
            if (!slotsArray || slotsArray.length === 0) return '';
            if (slotsArray.length === 1) return `#${slotsArray[0]}`;
            const min = Math.min(...slotsArray);
            const max = Math.max(...slotsArray);
            const isConsec = (max - min + 1 === slotsArray.length);
            return isConsec ? `#${min} - #${max}` : slotsArray.map(s => `#${s}`).join(', ');
        };

        if (uniqueCount === 1 && uniquePrizes[0]) {
            const heroData = uniquePrizes[0];
            const hero = heroData.prize;
            const heroSlots = heroData.slots;
            const slotRangeText = formatSlotRange(heroSlots);
            const winnerCountText = heroSlots.length > 1 
                ? `${heroSlots.length} PEMENANG` 
                : '1 PEMENANG';

            const heroImg = hero.image_url 
                ? `<img src="${hero.image_url}" class="prize-hero-img" alt="${hero.name || ''}" />`
                : `<div class="prize-hero-icon-fallback">🎁</div>`;

            showcaseDeck.innerHTML = `
                <div class="prize-card-hero" id="prizeCardHero">
                    <div class="prize-hero-img-box">
                        <div class="prize-chip-pin">${slotRangeText}</div>
                        ${heroImg}
                    </div>
                    <div class="prize-hero-info">
                        <div class="prize-hero-badge-pill">
                            <span class="badge-sparkle">✦</span>
                            <span>DOORPRIZE &bull; ${winnerCountText}</span>
                            <span class="badge-sparkle">✦</span>
                        </div>
                        <div class="prize-hero-title">${(hero.name || 'Hadiah Doorprize').toUpperCase()}</div>
                    </div>
                </div>
            `;
        } else if (uniqueCount <= 3) {
            let cardsHtml = `<div class="prize-cards-grid" id="prizeCardsGrid" data-count="${uniqueCount}">`;

            uniquePrizes.forEach((uItem, uIdx) => {
                const p = uItem.prize;
                const pSlots = uItem.slots;
                const pName = p?.name || `Hadiah #${uIdx + 1}`;
                const pImg = p?.image_url
                    ? `<img src="${p.image_url}" class="prize-chip-img" id="topPrizeImg-${uIdx}" alt="${pName}" />`
                    : `<div class="prize-chip-icon" id="topPrizeIcon-${uIdx}">🎁</div>`;
                const slotPin = formatSlotRange(pSlots);

                cardsHtml += `
                    <div class="prize-card-chip" id="topPrizeChip-${uIdx}" data-slots="${pSlots.join(',')}">
                        <div class="prize-chip-img-box">
                            <div class="prize-chip-pin">${slotPin}</div>
                            ${pImg}
                        </div>
                        <div class="prize-chip-name" id="topPrizeName-${uIdx}">${pName.toUpperCase()}</div>
                    </div>
                `;
            });
            cardsHtml += '</div>';
            showcaseDeck.innerHTML = cardsHtml;
        } else {
            // >= 4 Hadiah: Smooth Continuous Running Marquee (Reel)
            let groupList = uniquePrizes;
            if (groupList.length < 6) {
                groupList = [...groupList, ...groupList];
            }
            const duration = Math.max(30, Math.round(groupList.length * 3.2));

            let cardsHtml = '';
            groupList.forEach((uItem, uIdx) => {
                const p = uItem.prize;
                const pSlots = uItem.slots;
                const pName = p?.name || `Hadiah #${uIdx + 1}`;
                const pImg = p?.image_url
                    ? `<img src="${p.image_url}" class="prize-chip-img" alt="${pName}" />`
                    : `<div class="prize-chip-icon">🎁</div>`;
                const slotPin = formatSlotRange(pSlots);

                cardsHtml += `
                    <div class="prize-card-chip" data-slots="${pSlots.join(',')}">
                        <div class="prize-chip-img-box">
                            <div class="prize-chip-pin">${slotPin}</div>
                            ${pImg}
                        </div>
                        <div class="prize-chip-name">${pName.toUpperCase()}</div>
                    </div>
                `;
            });

            showcaseDeck.innerHTML = `
                <div class="prize-marquee-viewport" data-count="${uniqueCount}" style="--marquee-duration: ${duration}s;">
                    <div class="prize-marquee-track">
                        <div class="marquee-group">
                            ${cardsHtml}
                        </div>
                        <div class="marquee-group" aria-hidden="true">
                            ${cardsHtml}
                        </div>
                    </div>
                </div>
            `;
        }

        // Update tags in standby bars & revealed bars
        for (let i = 0; i < 10; i++) {
            const idleName = document.getElementById(`idlePrizeName-${i}`);
            const winnerName = document.getElementById(`winnerPrizeName-${i}`);
            const p = mapped[i] || getPrizeForSlot(i, prizes, plan);
            const pName = p?.name || `Hadiah #${i + 1}`;

            if (idleName) idleName.textContent = pName;
            if (winnerName && (!activeWinnerDetails[i] || !activeWinnerDetails[i].prize)) {
                winnerName.textContent = pName;
            }
        }
    }

    // Dynamic Slot Layout Manager (Mendukung 1 s/d 10 Pemenang & Kategori BE)
    function updateSlotsLayout(newSlots, activeCategories = []) {
        newSlots = Math.max(1, Math.min(10, Number(newSlots) || 5));
        currentSlots = newSlots;

        if (idleBarsStack) idleBarsStack.dataset.slots = newSlots;
        if (resultBarsStack) resultBarsStack.dataset.slots = newSlots;

        const stageCanvas = document.querySelector('.stage-canvas-16-9');
        if (stageCanvas) stageCanvas.dataset.slots = newSlots;

        const spotlightZone = document.getElementById('tvPrizeSpotlightZone');
        if (spotlightZone) spotlightZone.dataset.slots = newSlots;

        const stageRightZone = document.getElementById('stageRightZone');
        if (stageRightZone) stageRightZone.dataset.slots = newSlots;

        const showcaseDeck = document.getElementById('prizeShowcaseDeck');
        if (showcaseDeck) showcaseDeck.dataset.slots = newSlots;

        [idleBarsStack, resultBarsStack].forEach(stack => {
            if (!stack) return;
            stack.classList.remove('single-slot', 'two-cols');
            if (newSlots === 1) {
                stack.classList.add('single-slot');
            } else if (newSlots > 5) {
                stack.classList.add('two-cols');
            }
        });

        const gachaCard = document.querySelector('.gacha-mockup-card');
        if (gachaCard) {
            gachaCard.classList.remove('slots-1', 'slots-2', 'slots-3', 'slots-4', 'slots-5', 'slots-multi');
            if (newSlots > 5) {
                gachaCard.classList.add('slots-multi');
            } else {
                gachaCard.classList.add(`slots-${newSlots}`);
            }
        }

        const rowHeights = { 1: 180, 2: 170, 3: 150, 4: 135, 5: 128 };
        const topOffsets = { 1: 230, 2: 130, 3: 75, 4: 30, 5: 10 };
        const rowSpacing = { 1: 0, 2: 210, 3: 175, 4: 145, 5: 128 };

        for (let i = 0; i < 10; i++) {
            const idleBar = document.getElementById(`idleBar-${i}`);
            const resultBar = document.getElementById(`winnerBar-${i}`);
            const gachaRow = document.getElementById(`gachaLineRow-${i}`);
            const divider = document.querySelector(`.div-line-${i}`);

            const isVisible = i < newSlots;

            if (idleBar) idleBar.style.display = isVisible ? 'flex' : 'none';
            if (resultBar) resultBar.style.display = isVisible ? 'flex' : 'none';
            if (gachaRow) {
                gachaRow.style.display = isVisible ? 'flex' : 'none';
                if (newSlots <= 5) {
                    const h = rowHeights[newSlots] || 128;
                    const top = (topOffsets[newSlots] || 10) + i * (rowSpacing[newSlots] || 128);
                    gachaRow.style.top = `${top}px`;
                    gachaRow.style.height = `${h}px`;
                    gachaRow.dataset.col = "0";
                } else {
                    const half = Math.ceil(newSlots / 2);
                    const col = i < half ? 0 : 1;
                    const rowInCol = col === 0 ? i : (i - half);
                    let top = 15 + rowInCol * 125;
                    let h = 120;
                    if (half === 3) {
                        top = 90 + rowInCol * 170;
                        h = 145;
                    } else if (half === 4) {
                        top = 40 + rowInCol * 145;
                        h = 135;
                    }
                    gachaRow.style.top = `${top}px`;
                    gachaRow.style.height = `${h}px`;
                    gachaRow.dataset.col = String(col);
                }
            }

            if (divider) {
                divider.style.display = (i > 0 && i < newSlots && newSlots <= 5) ? 'block' : 'none';
                if (newSlots === 5) {
                    divider.style.top = `${10 + i * 128}px`;
                } else if (newSlots === 4) {
                    divider.style.top = `${30 + i * 145}px`;
                } else if (newSlots === 3) {
                    divider.style.top = `${75 + i * 175}px`;
                } else if (newSlots === 2) {
                    divider.style.top = `${130 + i * 210}px`;
                }
            }
        }

        const badgeTextEl = document.getElementById('gachaBadgeText');
        if (badgeTextEl) {
            let catText = '';
            if (Array.isArray(activeCategories) && activeCategories.length === 1) {
                catText = ` (${activeCategories[0]})`;
            }
            badgeTextEl.textContent = `Mengacak ${newSlots} Nama Pemenang${catText}...`;
        }

        slotLocked = Array(newSlots).fill(false);
        activeWinners = Array(newSlots).fill(null);
        activeWinnerDetails = Array(newSlots).fill(null);
    }

    // View Containers
    const viewIdle = document.getElementById('viewIdle');
    const viewSpinning = document.getElementById('viewSpinning');
    const viewResult = document.getElementById('viewResult');

    // Header & Status Texts
    const spinHeaderTxt = document.getElementById('spinHeaderTxt');
    const spinLiveStatus = document.getElementById('spinLiveStatus');

    // Celebration Modal Elements
    const popupCelebrationModal = document.getElementById('popupCelebrationModal');
    const popupWinnersGrid = document.getElementById('popupWinnersGrid');
    const btnCloseCelebrationModal = document.getElementById('btnCloseCelebrationModal');

    // 2. View Switching Helper
    function switchView(viewName) {
        const canvas = document.getElementById('stageCanvas');
        if (canvas) {
            canvas.classList.remove('state-idle', 'state-spinning', 'state-result', 'state-stopped');
            canvas.classList.add('state-' + (viewName === 'stopped' ? 'result' : viewName));
        }

        if (viewIdle) viewIdle.classList.remove('active');
        if (viewSpinning) viewSpinning.classList.remove('active');
        if (viewResult) viewResult.classList.remove('active');

        if (viewName === 'idle') {
            if (viewIdle) viewIdle.classList.add('active');
        } else if (viewName === 'spinning') {
            if (viewSpinning) viewSpinning.classList.add('active');
        } else if (viewName === 'result' || viewName === 'stopped') {
            if (viewResult) viewResult.classList.add('active');
        }
    }

    // 3. Web Audio API (Tick, Chime, Fanfare)
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
            osc.frequency.setValueAtTime(680, t);
            osc.frequency.exponentialRampToValueAtTime(140, t + 0.025);

            gain.gain.setValueAtTime(0.04, t);
            gain.gain.exponentialRampToValueAtTime(0.0001, t + 0.025);

            osc.connect(gain);
            gain.connect(ctx.destination);
            osc.start(t);
            osc.stop(t + 0.028);
        } catch (e) {}
    }

    function playChime(step = 0) {
        if (!soundEnabled) return;
        try {
            const ctx = getAudioContext();
            const t = ctx.currentTime;
            // Nada pentatonis mewah naik: C5, D5, E5, G5, A5, C6
            const freqs = [523.25, 587.33, 659.25, 783.99, 880.00, 1046.50];
            const f = freqs[step % freqs.length];

            const osc = ctx.createOscillator();
            const gain = ctx.createGain();
            osc.type = 'triangle';
            osc.frequency.setValueAtTime(f, t);

            gain.gain.setValueAtTime(0.18, t);
            gain.gain.exponentialRampToValueAtTime(0.0001, t + 0.65);

            osc.connect(gain);
            gain.connect(ctx.destination);
            osc.start(t);
            osc.stop(t + 0.65);
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
                    gain.gain.setValueAtTime(0.14, t);
                    gain.gain.exponentialRampToValueAtTime(0.0001, t + 0.95);
                    osc.connect(gain);
                    gain.connect(ctx.destination);
                    osc.start(t);
                    osc.stop(t + 0.95);
                }, idx * 120);
            });
        } catch (e) {}
    }

    function toggleSound() {
        soundEnabled = !soundEnabled;
    }

    // 4. Confetti Celebration
    function triggerLuxuryConfetti() {
        if (typeof confetti !== 'function') return;

        const count = 220;
        const defaults = {
            origin: { y: 0.6 },
            zIndex: 1200,
            colors: ['#fde68a', '#d4af37', '#f59e0b', '#881329', '#ffffff', '#b45309']
        };

        function fire(particleRatio, opts) {
            confetti(Object.assign({}, defaults, opts, {
                particleCount: Math.floor(count * particleRatio)
            }));
        }

        fire(0.25, { spread: 26, startVelocity: 55 });
        fire(0.2, { spread: 60 });
        fire(0.35, { spread: 100, decay: 0.91, scalar: 0.8 });
        fire(0.1, { spread: 120, startVelocity: 25, decay: 0.92, scalar: 1.2 });
        fire(0.1, { spread: 120, startVelocity: 45 });
    }

    // 5. Candidate Name Helpers
    function getName(item) {
        if (!item) return '-';
        if (typeof item === 'string') return item;
        return item.name || item.guest_name || item.title || '-';
    }

    function getCategory(item) {
        if (!item || typeof item !== 'object') return '';
        return item.category || item.group || item.table || '';
    }

    function pickRandomCandidate(excludedNames = []) {
        if (!participantPool || participantPool.length === 0) {
            return `Tamu #${Math.floor(Math.random() * 100) + 1}`;
        }
        const available = participantPool.filter(p => !excludedNames.includes(getName(p)));
        const pool = available.length > 0 ? available : participantPool;
        const idx = Math.floor(Math.random() * pool.length);
        return pool[idx];
    }

    // 6. GACHA ANIMATION: 5 Baris Horisontal dengan Arrow pada Setiap Baris
    let spinInterval = null;
    let peripheralInterval = null;
    const gachaBadgeText = document.getElementById('gachaBadgeText');

    function startSpinningAnimation() {
        if (isSpinning) return;
        isSpinning = true;

        slotLocked = Array(currentSlots).fill(false);
        if (gachaBadgeText) gachaBadgeText.textContent = `Mengacak ${currentSlots} Nama Pemenang...`;

        // Reset visual state pada ke-5 baris
        for (let i = 0; i < currentSlots; i++) {
            const line = document.getElementById(`gachaLineRow-${i}`);
            const pillCenter = document.getElementById(`rPillCenter-${i}`);
            if (line) line.classList.remove('locked');
            if (pillCenter) pillCenter.classList.remove('winner-locked');
        }

        switchView('spinning');

        // Target Center Pill berputar cepat (65ms) di setiap baris yang belum terkunci
        if (spinInterval) clearInterval(spinInterval);
        spinInterval = setInterval(() => {
            for (let i = 0; i < currentSlots; i++) {
                if (!slotLocked[i]) {
                    const candidate = pickRandomCandidate();
                    const elTarget = document.getElementById(`rTargetName-${i}`);
                    if (elTarget) elTarget.textContent = getName(candidate);
                }
            }
            playTick();
        }, 65);

        // Peripheral pills berganti setiap 130ms menciptakan ilusi roda kasino bergerak
        if (peripheralInterval) clearInterval(peripheralInterval);
        peripheralInterval = setInterval(() => {
            for (let i = 0; i < currentSlots; i++) {
                if (!slotLocked[i]) {
                    const elLE = document.getElementById(`rPillLeftEdge-${i}`);
                    const elL = document.getElementById(`rPillLeft-${i}`);
                    const elR = document.getElementById(`rPillRight-${i}`);
                    const elRE = document.getElementById(`rPillRightEdge-${i}`);

                    if (elLE) elLE.textContent = getName(pickRandomCandidate());
                    if (elL) elL.textContent = getName(pickRandomCandidate());
                    if (elR) elR.textContent = getName(pickRandomCandidate());
                    if (elRE) elRE.textContent = getName(pickRandomCandidate());
                }
            }
        }, 130);
    }

    function stopSpinningAnimation() {
        if (spinInterval) {
            clearInterval(spinInterval);
            spinInterval = null;
        }
        if (peripheralInterval) {
            clearInterval(peripheralInterval);
            peripheralInterval = null;
        }
        isSpinning = false;
    }

    // 7. DISPLAY RESULTS: Dramatis Mengunci 1 per 1 di Setiap Baris (Line 1 s/d Line 5)
    function displayResults(winners, { winnerDetails = null, instant = false } = {}) {
        if (!Array.isArray(winners) || winners.length === 0) return;

        if (winners.length !== currentSlots) {
            updateSlotsLayout(winners.length);
        }

        activeWinners = [...winners];
        activeWinnerDetails = winnerDetails ? [...winnerDetails] : Array(winners.length).fill(null);

        // Update teks di viewResult (5 Baris Horizontal Emas)
        winners.forEach((winner, idx) => {
            const nameEl = document.getElementById(`winnerName-${idx}`);
            const catEl = document.getElementById(`winnerCat-${idx}`);
            const prizeEl = document.getElementById(`winnerPrizeName-${idx}`);
            if (nameEl) nameEl.textContent = getName(winner);

            const detail = (winnerDetails && winnerDetails[idx]) ? winnerDetails[idx] : winner;
            const wCat = getCategory(detail);
            if (catEl) {
                if (wCat) {
                    catEl.textContent = wCat;
                    catEl.classList.add('visible');
                } else {
                    catEl.textContent = '';
                    catEl.classList.remove('visible');
                }
            }

            const prizeName = (detail && typeof detail === 'object' && detail.prize) 
                ? detail.prize 
                : (getPrizeForSlot(idx)?.name || `Hadiah #${idx + 1}`);
            if (prizeEl) prizeEl.textContent = prizeName;
        });

        if (instant) {
            stopSpinningAnimation();
            switchView('result');
            return;
        }

        // Penguncian Dramatis 1 per 1 di Setiap Baris
        const total = Math.min(winners.length, currentSlots);
        const lockStepDelay = 500; // 500ms jeda dramatis antar slot

        for (let i = 0; i < total; i++) {
            setTimeout(() => {
                slotLocked[i] = true;
                const line = document.getElementById(`gachaLineRow-${i}`);
                const pillCenter = document.getElementById(`rPillCenter-${i}`);
                const elTarget = document.getElementById(`rTargetName-${i}`);

                const winnerItem = winners[i];
                if (elTarget) elTarget.textContent = getName(winnerItem);

                if (line) line.classList.add('locked');
                if (pillCenter) pillCenter.classList.add('winner-locked');

                playChime(i);

                // Percikan konfeti mini di baris yang terkunci
                try {
                    const slotEl = document.getElementById(`lineSlot-${i}`);
                    if (slotEl && typeof confetti === 'function') {
                        const rect = slotEl.getBoundingClientRect();
                        confetti({
                            particleCount: 28,
                            spread: 60,
                            origin: {
                                x: (rect.left + rect.width * 0.5) / window.innerWidth,
                                y: (rect.top + rect.height * 0.5) / window.innerHeight
                            },
                            colors: ['#fde68a', '#d4af37', '#ffffff']
                        });
                    }
                } catch (e) {}

            }, i * lockStepDelay);
        }

        // Setelah semua baris terkunci: Fanfare, Confetti Penuh, lalu Beri Waktu Audiens Menikmati Hasil
        setTimeout(() => {
            stopSpinningAnimation();
            playFanfare();
            triggerLuxuryConfetti();

            if (gachaBadgeText) gachaBadgeText.textContent = `🎉 ${total} Pemenang Berhasil Terpilih!`;

            // Tampilkan modal popup jika ada
            if (popupWinnersGrid) {
                popupWinnersGrid.innerHTML = '';
                winners.forEach((w, idx) => {
                    const detail = (winnerDetails && winnerDetails[idx]) ? winnerDetails[idx] : w;
                    const prizeName = (detail && typeof detail === 'object' && detail.prize)
                        ? detail.prize
                        : (getPrizeForSlot(idx)?.name || `Hadiah #${idx + 1}`);

                    const row = document.createElement('div');
                    row.className = 'popup-winner-row';
                    row.innerHTML = `
                        <div class="popup-winner-rank">${idx + 1}</div>
                        <div class="popup-winner-info">
                            <div class="popup-winner-name">${getName(w)}</div>
                            <div class="popup-winner-prize-pill">🎁 ${prizeName}</div>
                        </div>
                    `;
                    popupWinnersGrid.appendChild(row);
                });
            }

            // Tampilkan kartu gacha dengan semua baris terkunci selama 2.6 detik, lalu transisi mulus ke viewResult
            setTimeout(() => {
                switchView('result');
            }, 2600);

        }, total * lockStepDelay + 300);
    }

    // 8. Standalone Local Spin (Bisa diuji dengan SPASI di TV)
    function startLocalSpin() {
        if (isSpinning) return;
        startSpinningAnimation();

        // Ambil pemenang acak tanpa duplikasi
        const chosen = [];
        const poolCopy = [...participantPool];
        for (let i = poolCopy.length - 1; i > 0; i--) {
            const j = Math.floor(Math.random() * (i + 1));
            [poolCopy[i], poolCopy[j]] = [poolCopy[j], poolCopy[i]];
        }

        for (let i = 0; i < currentSlots; i++) {
            chosen.push(poolCopy[i] || `Pemenang #${i + 1}`);
        }

        // Putar selama 3.5 detik lalu kunci satu per satu
        setTimeout(() => {
            displayResults(chosen, { instant: false });
        }, 3500);
    }

    // 9. Reset ke Standby
    function resetToIdle() {
        stopSpinningAnimation();
        isSpinning = false;
        slotLocked = Array(currentSlots).fill(false);
        activeWinners = Array(currentSlots).fill(null);
        activeWinnerDetails = Array(currentSlots).fill(null);

        for (let i = 0; i < currentSlots; i++) {
            const line = document.getElementById(`gachaLineRow-${i}`);
            const pillCenter = document.getElementById(`rPillCenter-${i}`);
            if (line) line.classList.remove('locked');
            if (pillCenter) pillCenter.classList.remove('winner-locked');

            const nameEl = document.getElementById(`winnerName-${i}`);
            const catEl = document.getElementById(`winnerCat-${i}`);
            if (nameEl) nameEl.textContent = '-';
            if (catEl) {
                catEl.textContent = '';
                catEl.classList.remove('visible');
            }
        }

        switchView('idle');
    }

    // 10. Keyboard Shortcuts (Untuk kemudahan operator / testing tanpa tombol visual)
    window.addEventListener('keydown', (e) => {
        if (e.code === 'Space' || e.code === 'Enter') {
            if (!isSpinning && popupCelebrationModal && !popupCelebrationModal.classList.contains('active')) {
                e.preventDefault();
                startLocalSpin();
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
            if (!isSpinning) resetToIdle();
        } else if (e.key === 'c' || e.key === 'C') {
            if (!isSpinning) {
                triggerLuxuryConfetti();
                playFanfare();
            }
        } else if (e.key === 'Escape') {
            if (popupCelebrationModal) popupCelebrationModal.classList.remove('active');
        }
    });

    if (btnCloseCelebrationModal) {
        btnCloseCelebrationModal.addEventListener('click', () => {
            if (popupCelebrationModal) popupCelebrationModal.classList.remove('active');
        });
    }

    // Dengarkan perubahan alokasi hadiah dari dasbor operator secara instan antar-tab
    window.addEventListener('storage', (e) => {
        if (e.key === 'doorprize.allocation' || e.key === 'doorprize.slots') {
            renderPrizeShowcase();
        }
    });

    let lastAllocationRaw = localStorage.getItem('doorprize.allocation');
    let lastSlotsRaw = localStorage.getItem('doorprize.slots');
    setInterval(() => {
        if (!isSpinning && (!activeWinners || !activeWinners.some(w => w !== null))) {
            const currentAllocationRaw = localStorage.getItem('doorprize.allocation');
            const currentSlotsRaw = localStorage.getItem('doorprize.slots');
            if (currentAllocationRaw !== lastAllocationRaw || currentSlotsRaw !== lastSlotsRaw) {
                lastAllocationRaw = currentAllocationRaw;
                lastSlotsRaw = currentSlotsRaw;
                renderPrizeShowcase();
            }
        }
    }, 1000);

    // 11. Remote Synchronization dengan Dasbor Admin (/doorprize)
    const remote = window.doorprizeSpin || null;
    let remoteSeq = remote ? remote.seq : 0;

    function applyRemote(state, { initial = false } = {}) {
        if (Array.isArray(state.pool) && state.pool.length > 0) {
            participantPool = state.pool;
        }

        const targetSlots = Number(state.slots) || currentSlots || 5;
        const targetCats = Array.isArray(state.categories) ? state.categories : [];

        if (targetSlots !== currentSlots || initial) {
            updateSlotsLayout(targetSlots, targetCats);
        }

        if (state.prizes || state.prize_plan || targetSlots !== currentSlots || initial) {
            renderPrizeShowcase(
                Array.isArray(state.prizes) ? state.prizes : currentPrizes,
                Array.isArray(state.prize_plan) ? state.prize_plan : currentPrizePlan,
                targetSlots
            );
        }

        if (state.status === 'spinning') {
            if (!isSpinning) {
                startSpinningAnimation();
            }
            if (typeof state.remaining_ms === 'number' && state.remaining_ms > 0 && gachaBadgeText) {
                const sec = Math.ceil(state.remaining_ms / 1000);
                const catText = targetCats.length === 1 ? ` (${targetCats[0]})` : '';
                gachaBadgeText.textContent = `Mengacak ${targetSlots} Nama Pemenang${catText} [${sec}s]...`;
            }
        } else if (state.status === 'stopped' && Array.isArray(state.winners) && state.winners.length > 0) {
            displayResults(state.winners, {
                winnerDetails: state.winner_details,
                instant: initial
            });
        } else if (state.status === 'idle' && !initial) {
            resetToIdle();
        }
    }

    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('slots')) {
        updateSlotsLayout(Number(urlParams.get('slots')));
    }

    renderPrizeShowcase();

    const isPreviewParam = window.location.search.includes('preview=');
    if (isPreviewParam) {
        if (window.location.search.includes('preview=spinning')) {
            startSpinningAnimation();
        }
    } else if (remote) {
        applyRemote(remote, { initial: true });

        const stateUrl = window.doorprizeSpinStateUrl;
        let currentRemoteStatus = remote ? remote.status : 'idle';
        let currentRemoteSlots = remote ? Number(remote.slots) : currentSlots;
        setInterval(async () => {
            try {
                const response = await fetch(`${stateUrl}?seq=${remoteSeq}`, {
                    headers: { 'Accept': 'application/json' }
                });
                const state = await response.json();

                const seqChanged = state.seq !== remoteSeq;
                const statusChanged = state.status !== currentRemoteStatus;
                const slotsChanged = Number(state.slots) !== currentRemoteSlots;
                const planChanged = JSON.stringify(state.prize_plan || []) !== JSON.stringify(currentPrizePlan || []);

                if (seqChanged || statusChanged || slotsChanged || planChanged) {
                    remoteSeq = state.seq;
                    currentRemoteStatus = state.status;
                    if (state.slots) currentRemoteSlots = Number(state.slots);
                    applyRemote(state);
                }
            } catch (e) {}
        }, 1000);
    }
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initDoorprizeTv);
} else {
    initDoorprizeTv();
}
