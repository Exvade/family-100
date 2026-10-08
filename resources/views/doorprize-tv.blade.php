<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wedding Doorprize - Anne &amp; Hanif</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}" type="image/x-icon">

    <!-- Luxury Typography Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@600;700;800;900&family=Great+Vibes&family=Montserrat:wght@400;500;600;700;800&family=Playfair+Display:ital,wght@0,600;0,700;0,800;0,900;1,600;1,700&display=swap" rel="stylesheet">

    <!-- Canvas Confetti CDN -->
    <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.9.4/dist/confetti.browser.min.js"></script>

    <!-- Burgundy TV Stylesheet -->
    <link rel="stylesheet" href="{{ asset('css/doorprize-tv.css') }}">
</head>
<body class="theme-burgundy">

    @php
        $previewMode = request('preview');
        $previewLocked = request()->has('preview_locked');
        $currentStatus = $previewMode ?: ($spin['status'] ?? 'idle');
        if ($previewMode === 'result') {
            $currentStatus = 'stopped';
        }
        $defaultPreviewWinners = ['Rizky Pratama', 'Siti Aulia', 'Dimas Arya', 'Nadya Putri', 'Fauzan Hakim'];
        $winnersList = (!empty($spin['winners']) && is_array($spin['winners'])) ? $spin['winners'] : $defaultPreviewWinners;
        $winnerDetailsList = $spin['winner_details'] ?? [];
        $slotCount = $slots ?? 5;
    @endphp

    <!-- OUTER VIEWPORT SHELL (Centers and clips stage) -->
    <div class="stage-viewport-shell" id="stageShell">

        <!-- 16:9 PROPORTIONAL SCALED CANVAS (Auto-scales on Ctrl - / Ctrl + and any resolution) -->
        <div class="stage-canvas-16-9 state-{{ $currentStatus }}" id="stageCanvas">

            <!-- 1. LUXURY GOLD BORDERS AROUND ENTIRE CANVAS -->
            <div class="stage-border-outer"></div>
            <div class="stage-border-inner"></div>

            <!-- 2. CORNER FLORALS (Top-Left, Top-Right, Bottom-Left, Bottom-Right) -->
            <img src="{{ asset('images/floral-tl.png') }}" class="stage-floral stage-floral-tl" alt="Floral TL" />
            <img src="{{ asset('images/floral-tr.png') }}" class="stage-floral stage-floral-tr" alt="Floral TR" />
            <img src="{{ asset('images/floral-bl.png') }}" class="stage-floral stage-floral-bl" alt="Floral BL" />
            <img src="{{ asset('images/floral-br.png') }}" class="stage-floral stage-floral-br" alt="Floral BR" />

            <!-- 3. BOTTOM VELVET DRAPERY SHADOW -->
            <div class="stage-velvet-drape"></div>

            <!-- 4. LEFT STAGE: ROMAN ARCH WITH PREWEDDING COUPLE PHOTO (TETAP UTUH & TIDAK PERNAH MENYUSUT) -->
            <div class="stage-left-zone" id="stageLeftZone">
                <div class="couple-arch-card" id="coupleArchCard">
                    <div class="couple-arch-inner">
                        <img src="{{ asset('images/prewed.webp') }}" class="couple-photo-img" alt="Anne & Hanif Prewedding" />
                        <div class="couple-arch-vignette"></div>
                    </div>
                </div>
            </div>

            <!-- 5. WEDDING TITLE HEADER (TETAP DI KANAN ATAS PADA SEMUA STATE: IDLE, GACHA, RESULT) -->
            <header class="wedding-title-header" id="weddingTitleHeader">
                <div class="wedding-hearts-icon">
                    <svg viewBox="0 0 64 42" width="58" height="38" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M22 36 C10 26 2 18 2 10 C2 4 7 0 13 0 C18 0 21 3 22 5 C23 3 26 0 31 0 C37 0 42 4 42 10 C42 18 34 26 22 36 Z" stroke="url(#goldH1)" stroke-width="2.6" fill="none" stroke-linejoin="round"/>
                        <path d="M42 36 C30 26 22 18 22 10 C22 4 27 0 33 0 C38 0 41 3 42 5 C43 3 46 0 51 0 C57 0 62 4 62 10 C62 18 54 26 42 36 Z" stroke="url(#goldH2)" stroke-width="2.4" fill="none" stroke-linejoin="round"/>
                        <defs>
                            <linearGradient id="goldH1" x1="0" y1="0" x2="1" y2="1">
                                <stop offset="0%" stop-color="#fff8ed"/>
                                <stop offset="50%" stop-color="#fde68a"/>
                                <stop offset="100%" stop-color="#d97706"/>
                            </linearGradient>
                            <linearGradient id="goldH2" x1="0" y1="0" x2="1" y2="1">
                                <stop offset="0%" stop-color="#fde68a"/>
                                <stop offset="50%" stop-color="#d4af37"/>
                                <stop offset="100%" stop-color="#92400e"/>
                            </linearGradient>
                        </defs>
                    </svg>
                </div>
                <h2 class="wedding-script-title">Wedding</h2>
                <h1 class="wedding-names-title">Anne &amp; Hanif</h1>
                <div class="wedding-gold-divider">
                    <span class="div-line"></span>
                    <span class="div-heart">♥</span>
                    <span class="div-line"></span>
                </div>
            </header>

            <!-- 6. RIGHT STAGE: INTERACTIVE PANELS (TANPA TOMBOL KONTROL, FULL CONTROL DARI DASHBOARD) -->
            <div class="stage-right-zone" id="stageRightZone">

                <!-- Interactive State Panels: Idle, Spinning (5 Lines Roulette Card), Result -->
                <main class="interactive-stage-panel">

                    <!-- ==================== 1. IDLE / STANDBY STATE ==================== -->
                    <div class="view-panel view-panel-idle {{ $currentStatus === 'idle' ? 'active' : '' }}" id="viewIdle">
                        <div class="bars-vertical-stack {{ $slotCount === 1 ? 'single-slot' : ($slotCount > 5 ? 'two-cols' : '') }}" id="idleBarsStack" data-slots="{{ $slotCount }}">
                            @for ($i = 0; $i < $slotCount; $i++)
                                <div class="winner-entry-bar standby" id="idleBar-{{ $i }}" data-index="{{ $i }}">
                                    <div class="bar-rank-badge">{{ $i + 1 }}</div>
                                    <div class="bar-name-card standby-card">
                                        <div class="standby-text-wrap">
                                            <span class="standby-shimmer-dots">✦ &nbsp; ✦ &nbsp; ✦ &nbsp; ✦ &nbsp; ✦ &nbsp; ✦ &nbsp; ✦</span>
                                            <span class="standby-label-badge">SIAP DIUNDI</span>
                                        </div>
                                        <div class="botanical-gold-leaf">
                                            <svg viewBox="0 0 130 80" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M125 45 C100 42 70 38 15 36" stroke="url(#goldStemG-idle-{{ $i }})" stroke-width="2" stroke-linecap="round"/>
                                                <path d="M90 40 C75 25 55 18 35 15" stroke="url(#goldStemG-idle-{{ $i }})" stroke-width="1.4" stroke-linecap="round"/>
                                                <path d="M85 41 C70 55 50 62 30 65" stroke="url(#goldStemG-idle-{{ $i }})" stroke-width="1.4" stroke-linecap="round"/>
                                                <path d="M110 44 C116 32 108 20 96 26 C92 32 98 40 110 44 Z" fill="url(#goldLeafG-idle-{{ $i }})" opacity="0.45" stroke="#d4af37" stroke-width="0.8"/>
                                                <path d="M85 36 C90 22 78 14 68 20 C64 26 72 34 85 36 Z" fill="url(#goldLeafG-idle-{{ $i }})" opacity="0.45" stroke="#d4af37" stroke-width="0.8"/>
                                                <path d="M60 28 C64 16 52 10 44 16 C40 22 48 27 60 28 Z" fill="url(#goldLeafG-idle-{{ $i }})" opacity="0.4" stroke="#d4af37" stroke-width="0.8"/>
                                                <path d="M35 15 C38 6 28 3 22 8 C19 14 26 17 35 15 Z" fill="url(#goldLeafG-idle-{{ $i }})" opacity="0.4" stroke="#d4af37" stroke-width="0.8"/>
                                                <path d="M100 46 C105 58 95 68 85 62 C82 56 90 48 100 46 Z" fill="url(#goldLeafG-idle-{{ $i }})" opacity="0.45" stroke="#d4af37" stroke-width="0.8"/>
                                                <path d="M75 44 C80 58 68 66 58 60 C55 54 64 47 75 44 Z" fill="url(#goldLeafG-idle-{{ $i }})" opacity="0.45" stroke="#d4af37" stroke-width="0.8"/>
                                                <path d="M52 48 C55 60 45 66 38 62 C35 56 42 50 52 48 Z" fill="url(#goldLeafG-idle-{{ $i }})" opacity="0.4" stroke="#d4af37" stroke-width="0.8"/>
                                                <path d="M30 65 C32 74 22 76 18 70 C16 64 23 62 30 65 Z" fill="url(#goldLeafG-idle-{{ $i }})" opacity="0.4" stroke="#d4af37" stroke-width="0.8"/>
                                                <path d="M20 37 C12 36 6 30 8 24 C14 24 18 31 20 37 Z" fill="url(#goldLeafG-idle-{{ $i }})" opacity="0.4" stroke="#d4af37" stroke-width="0.8"/>
                                                <path d="M15 36 C8 38 4 45 7 50 C12 49 15 42 15 36 Z" fill="url(#goldLeafG-idle-{{ $i }})" opacity="0.4" stroke="#d4af37" stroke-width="0.8"/>
                                                <defs>
                                                    <linearGradient id="goldStemG-idle-{{ $i }}" x1="1" y1="1" x2="0" y2="0">
                                                        <stop offset="0%" stop-color="#fff8ed"/>
                                                        <stop offset="60%" stop-color="#d4af37"/>
                                                        <stop offset="100%" stop-color="#92400e"/>
                                                    </linearGradient>
                                                    <linearGradient id="goldLeafG-idle-{{ $i }}" x1="0" y1="0" x2="1" y2="1">
                                                        <stop offset="0%" stop-color="#fffbeb"/>
                                                        <stop offset="50%" stop-color="#fde68a"/>
                                                        <stop offset="100%" stop-color="#d97706"/>
                                                    </linearGradient>
                                                </defs>
                                            </svg>
                                        </div>
                                    </div>
                                </div>
                            @endfor
                        </div>
                    </div>

                    <!-- ==================== 2. SPINNING / GACHA STATE (5 LINES ROULETTE WITH DEDICATED ARROWS) ==================== -->
                    <div class="view-panel view-panel-spinning {{ $currentStatus === 'spinning' ? 'active' : '' }}" id="viewSpinning">
                        <div class="gacha-mockup-card gacha-card-5lines">

                            <!-- Top Header Badge: [ 🔀 ] Mengacak 5 Nama Pemenang... ─ -->
                            <div class="gacha-top-badge">
                                <div class="badge-shuffle-circle">
                                    <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="#ffffff" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round">
                                        <polyline points="16 3 21 3 21 8"></polyline>
                                        <line x1="4" y1="20" x2="21" y2="3"></line>
                                        <polyline points="21 16 21 21 16 21"></polyline>
                                        <line x1="15" y1="15" x2="21" y2="21"></line>
                                        <line x1="4" y1="4" x2="9" y2="9"></line>
                                    </svg>
                                </div>
                                <span class="badge-text" id="gachaBadgeText">Mengacak {{ $slotCount }} Nama Pemenang...</span>
                                <span class="badge-gold-dash"></span>
                            </div>

                            <!-- Horizontal Dividers Between Lines -->
                            @for ($d = 1; $d < $slotCount; $d++)
                                <div class="gacha-h-divider div-line-{{ $d }}" style="top: {{ 10 + $d * 128 }}px;"></div>
                            @endfor

                            <!-- 5 Horizontal Lines with Dedicated Target Slots & 3D Gold Arrows -->
                            @for ($i = 0; $i < $slotCount; $i++)
                                @php
                                    $defaultName = $winnersList[$i] ?? ('Pemenang #' . ($i + 1));
                                    $isLocked = $previewLocked && ($i < 3);
                                @endphp
                                <div class="gacha-line-row line-{{ $i }} {{ $isLocked ? 'locked' : '' }}" id="gachaLineRow-{{ $i }}" style="top: {{ 10 + $i * 128 }}px; height: 128px;" data-index="{{ $i }}">

                                    <!-- Subtle Luxury Gold Rank Badge -->
                                    <div class="line-gold-badge" id="lineBadge-{{ $i }}">{{ $i + 1 }}</div>

                                    <!-- Left Flanking Name Stream -->
                                    <div class="line-side-pills side-left {{ $i % 2 === 0 ? 'dir-rtl' : 'dir-ltr' }}">
                                        <div class="name-pill pill-edge" id="rPillLeftEdge-{{ $i }}">Tamu Undangan</div>
                                        <div class="name-pill pill-normal" id="rPillLeft-{{ $i }}">Calon Pemenang</div>
                                    </div>

                                    <!-- Center Target Slot with Dedicated Pointers (▼ & ▲) -->
                                    <div class="line-target-slot-container" id="lineSlot-{{ $i }}">

                                        <!-- 3D Gold Down Pointer (▼) -->
                                        <div class="line-pointer line-pointer-down" id="ptrDown-{{ $i }}">
                                            <svg viewBox="0 0 44 32" width="28" height="18" fill="none">
                                                <defs>
                                                    <linearGradient id="facetDownL-{{ $i }}" x1="0" y1="0" x2="1" y2="1"><stop offset="0%" stop-color="#ffffff"/><stop offset="40%" stop-color="#fde68a"/><stop offset="100%" stop-color="#d97706"/></linearGradient>
                                                    <linearGradient id="facetDownR-{{ $i }}" x1="1" y1="0" x2="0" y2="1"><stop offset="0%" stop-color="#fde68a"/><stop offset="60%" stop-color="#d4af37"/><stop offset="100%" stop-color="#78350f"/></linearGradient>
                                                    <filter id="glowDownArrow-{{ $i }}"><feDropShadow dx="0" dy="2" stdDeviation="3" flood-color="#f59e0b" flood-opacity="0.9"/></filter>
                                                </defs>
                                                <polygon points="22,29 3,5 22,5" fill="url(#facetDownL-{{ $i }})"/>
                                                <polygon points="22,29 22,5 41,5" fill="url(#facetDownR-{{ $i }})"/>
                                                <line x1="22" y1="5" x2="22" y2="29" stroke="#ffffff" stroke-width="1.2" opacity="0.9"/>
                                            </svg>
                                        </div>

                                        <!-- The Golden Target Winner Pill -->
                                        <div class="name-pill pill-target-center {{ $isLocked ? 'winner-locked' : '' }}" id="rPillCenter-{{ $i }}">
                                            <span class="target-name-txt" id="rTargetName-{{ $i }}">{{ $defaultName }}</span>
                                        </div>

                                        <!-- 3D Gold Up Pointer (▲) -->
                                        <div class="line-pointer line-pointer-up" id="ptrUp-{{ $i }}">
                                            <svg viewBox="0 0 44 32" width="28" height="18" fill="none">
                                                <defs>
                                                    <linearGradient id="facetUpL-{{ $i }}" x1="0" y1="1" x2="1" y2="0"><stop offset="0%" stop-color="#ffffff"/><stop offset="40%" stop-color="#fde68a"/><stop offset="100%" stop-color="#d97706"/></linearGradient>
                                                    <linearGradient id="facetUpR-{{ $i }}" x1="1" y1="1" x2="0" y2="0"><stop offset="0%" stop-color="#fde68a"/><stop offset="60%" stop-color="#d4af37"/><stop offset="100%" stop-color="#78350f"/></linearGradient>
                                                    <filter id="glowUpArrow-{{ $i }}"><feDropShadow dx="0" dy="-2" stdDeviation="3" flood-color="#f59e0b" flood-opacity="0.9"/></filter>
                                                </defs>
                                                <polygon points="22,3 3,27 22,27" fill="url(#facetUpL-{{ $i }})"/>
                                                <polygon points="22,3 22,27 41,27" fill="url(#facetUpR-{{ $i }})"/>
                                                <line x1="22" y1="27" x2="22" y2="3" stroke="#ffffff" stroke-width="1.2" opacity="0.9"/>
                                            </svg>
                                        </div>

                                    </div>

                                    <!-- Right Flanking Name Stream -->
                                    <div class="line-side-pills side-right {{ $i % 2 === 0 ? 'dir-rtl' : 'dir-ltr' }}">
                                        <div class="name-pill pill-normal" id="rPillRight-{{ $i }}">Calon Pemenang</div>
                                        <div class="name-pill pill-edge" id="rPillRightEdge-{{ $i }}">Tamu Undangan</div>
                                    </div>

                                </div>
                            @endfor

                        </div>
                    </div>

                    <!-- ==================== 3. RESULT STATE (REPLIKA IMAGE 2) ==================== -->
                    <div class="view-panel view-panel-result {{ $currentStatus === 'stopped' ? 'active' : '' }}" id="viewResult">
                        <div class="bars-vertical-stack {{ $slotCount === 1 ? 'single-slot' : ($slotCount > 5 ? 'two-cols' : '') }}" id="resultBarsStack" data-slots="{{ $slotCount }}">
                            @for ($i = 0; $i < $slotCount; $i++)
                                @php
                                    $initWinner = $winnersList[$i] ?? '-';
                                @endphp
                                <div class="winner-entry-bar revealed" id="winnerBar-{{ $i }}" data-index="{{ $i }}">
                                    <div class="bar-rank-badge">{{ $i + 1 }}</div>
                                    <div class="bar-name-card">
                                        <div class="winner-name-wrap">
                                            <div class="winner-person-name" id="winnerName-{{ $i }}">{{ $initWinner }}</div>
                                            <div class="winner-cat-badge" id="winnerCat-{{ $i }}"></div>
                                        </div>

                                        <!-- Botanical Leaf Watermark on Right -->
                                        <div class="botanical-gold-leaf">
                                            <svg viewBox="0 0 130 80" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M125 45 C100 42 70 38 15 36" stroke="url(#goldStemG-res-{{ $i }})" stroke-width="2" stroke-linecap="round"/>
                                                <path d="M90 40 C75 25 55 18 35 15" stroke="url(#goldStemG-res-{{ $i }})" stroke-width="1.4" stroke-linecap="round"/>
                                                <path d="M85 41 C70 55 50 62 30 65" stroke="url(#goldStemG-res-{{ $i }})" stroke-width="1.4" stroke-linecap="round"/>
                                                <path d="M110 44 C116 32 108 20 96 26 C92 32 98 40 110 44 Z" fill="url(#goldLeafG-res-{{ $i }})" opacity="0.45" stroke="#d4af37" stroke-width="0.8"/>
                                                <path d="M85 36 C90 22 78 14 68 20 C64 26 72 34 85 36 Z" fill="url(#goldLeafG-res-{{ $i }})" opacity="0.45" stroke="#d4af37" stroke-width="0.8"/>
                                                <path d="M60 28 C64 16 52 10 44 16 C40 22 48 27 60 28 Z" fill="url(#goldLeafG-res-{{ $i }})" opacity="0.4" stroke="#d4af37" stroke-width="0.8"/>
                                                <path d="M35 15 C38 6 28 3 22 8 C19 14 26 17 35 15 Z" fill="url(#goldLeafG-res-{{ $i }})" opacity="0.4" stroke="#d4af37" stroke-width="0.8"/>
                                                <path d="M100 46 C105 58 95 68 85 62 C82 56 90 48 100 46 Z" fill="url(#goldLeafG-res-{{ $i }})" opacity="0.45" stroke="#d4af37" stroke-width="0.8"/>
                                                <path d="M75 44 C80 58 68 66 58 60 C55 54 64 47 75 44 Z" fill="url(#goldLeafG-res-{{ $i }})" opacity="0.45" stroke="#d4af37" stroke-width="0.8"/>
                                                <path d="M52 48 C55 60 45 66 38 62 C35 56 42 50 52 48 Z" fill="url(#goldLeafG-res-{{ $i }})" opacity="0.4" stroke="#d4af37" stroke-width="0.8"/>
                                                <path d="M30 65 C32 74 22 76 18 70 C16 64 23 62 30 65 Z" fill="url(#goldLeafG-res-{{ $i }})" opacity="0.4" stroke="#d4af37" stroke-width="0.8"/>
                                                <path d="M20 37 C12 36 6 30 8 24 C14 24 18 31 20 37 Z" fill="url(#goldLeafG-res-{{ $i }})" opacity="0.4" stroke="#d4af37" stroke-width="0.8"/>
                                                <path d="M15 36 C8 38 4 45 7 50 C12 49 15 42 15 36 Z" fill="url(#goldLeafG-res-{{ $i }})" opacity="0.4" stroke="#d4af37" stroke-width="0.8"/>
                                                <defs>
                                                    <linearGradient id="goldStemG-res-{{ $i }}" x1="1" y1="1" x2="0" y2="0">
                                                        <stop offset="0%" stop-color="#fff8ed"/>
                                                        <stop offset="60%" stop-color="#d4af37"/>
                                                        <stop offset="100%" stop-color="#92400e"/>
                                                    </linearGradient>
                                                    <linearGradient id="goldLeafG-res-{{ $i }}" x1="0" y1="0" x2="1" y2="1">
                                                        <stop offset="0%" stop-color="#fffbeb"/>
                                                        <stop offset="50%" stop-color="#fde68a"/>
                                                        <stop offset="100%" stop-color="#d97706"/>
                                                    </linearGradient>
                                                </defs>
                                            </svg>
                                        </div>
                                    </div>
                                </div>
                            @endfor
                        </div>
                    </div>

                </main>

            </div>

        </div>

    </div>

    <!-- Celebration Fireworks Modal Popup -->
    <div class="celebration-popup-overlay" id="popupCelebrationModal">
        <div class="celebration-popup-card">
            <div class="popup-crown-emblem">👑</div>
            <h2 class="popup-header-title">SELAMAT KEPADA PARA PEMENANG!</h2>
            <p class="popup-header-sub">WEDDING DOORPRIZE • ANNE &amp; HANIF</p>

            <div class="popup-winners-grid" id="popupWinnersGrid"></div>

            <div class="popup-footer">
                <button class="btn-close-popup" id="btnCloseCelebrationModal" type="button">TUTUP / LANJUTKAN</button>
                <div class="popup-dismiss-hint">TEKAN [ESC] ATAU KLIK DI MANA SAJA UNTUK MENUTUP</div>
            </div>
        </div>
    </div>

    <!-- Direct Controller Data Injection -->
    <script>
        window.doorprizeParticipants = @json($participants ?? []);
        window.doorprizeWedding = @json($wedding ?? null);
        window.doorprizeSpin = @json($spin ?? null);
        window.doorprizeSpinStateUrl = @json(route('doorprize.tv.state'));
    </script>

    <!-- Burgundy TV Interactive Script -->
    <script src="{{ asset('js/doorprize-tv.js') }}"></script>
</body>
</html>
