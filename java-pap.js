document.addEventListener("DOMContentLoaded", function () {

    // ============================================================
    // 1. MENU ATIVO AUTOMÁTICO
    // Lê o nome do ficheiro atual e marca o link correspondente
    // ============================================================
    const paginaAtual = window.location.pathname.split("/").pop() || "index.php";

    document.querySelectorAll("nav a").forEach(link => {
        const href = link.getAttribute("href");
        if (href && href !== "#") {
            const nomeFicheiro = href.split("/").pop();
            if (nomeFicheiro === paginaAtual) {
                link.classList.add("active");
            }
        }
    });


    // ============================================================
    // 2. HEADER: ESCONDER NO SCROLL DOWN, MOSTRAR NO SCROLL UP
    //    + sombra extra ao fazer scroll
    // ============================================================
    let lastScrollTop = 0;
    const header = document.querySelector("header");

    if (header) {
        window.addEventListener("scroll", function () {
            const scrollTop = window.pageYOffset || document.documentElement.scrollTop;

            // Sombra mais forte quando não está no topo
            if (scrollTop > 10) {
                header.classList.add("scrolled");
            } else {
                header.classList.remove("scrolled");
            }

            // Esconde ao descer, mostra ao subir
            if (scrollTop > lastScrollTop && scrollTop > 100) {
                header.style.transform = "translateY(-100%)";
            } else {
                header.style.transform = "translateY(0)";
            }

            lastScrollTop = scrollTop <= 0 ? 0 : scrollTop;
        }, { passive: true });

        // Sempre visível quando o rato está perto do topo
        document.addEventListener("mousemove", function (e) {
            if (e.clientY <= 80) {
                header.style.transform = "translateY(0)";
            }
        });
    }


    // ============================================================
    // 3. EFEITO RIPPLE NOS BOTÕES AO CLICAR
    // ============================================================
    document.querySelectorAll("button[type='submit'], .btn-primary, .btn-schedule, .btn-pump").forEach(btn => {
        btn.addEventListener("click", function (e) {
            // Criar o círculo de ripple
            const ripple = document.createElement("span");
            const rect   = btn.getBoundingClientRect();
            const size   = Math.max(rect.width, rect.height);
            const x = e.clientX - rect.left - size / 2;
            const y = e.clientY - rect.top  - size / 2;

            ripple.style.cssText = `
                position: absolute;
                width: ${size}px;
                height: ${size}px;
                left: ${x}px;
                top: ${y}px;
                background: rgba(255,255,255,0.3);
                border-radius: 50%;
                transform: scale(0);
                animation: rippleAnim 0.5s ease-out forwards;
                pointer-events: none;
            `;

            // Garantir que o botão tem position relative para o ripple funcionar
            if (getComputedStyle(btn).position === "static") {
                btn.style.position = "relative";
            }
            btn.style.overflow = "hidden";
            btn.appendChild(ripple);

            setTimeout(() => ripple.remove(), 600);
        });
    });

    // CSS da animação do ripple (injetado uma vez)
    if (!document.getElementById("ripple-style")) {
        const style = document.createElement("style");
        style.id = "ripple-style";
        style.textContent = `
            @keyframes rippleAnim {
                to { transform: scale(2.5); opacity: 0; }
            }
        `;
        document.head.appendChild(style);
    }


    // ============================================================
    // 4. ANIMAÇÃO DE ENTRADA DOS CARTÕES (Intersection Observer)
    // Cartões que entram no viewport aparecem com fade+slide
    // ============================================================
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity  = "1";
                entry.target.style.transform = "translateY(0)";
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.1 });

    document.querySelectorAll(".card, .scheduling-section, .section-hardware, .edit-card").forEach(el => {
        // Estado inicial (só se não tiver animação CSS já aplicada)
        if (!el.style.animationName) {
            el.style.opacity   = "0";
            el.style.transform = "translateY(20px)";
            el.style.transition = "opacity 0.5s ease, transform 0.5s ease";
        }
        observer.observe(el);
    });


    // ============================================================
    // 5. TRANSIÇÃO SUAVE ENTRE PÁGINAS
    // Fade out antes de navegar para outro link interno
    // ============================================================
    document.querySelectorAll("a[href]").forEach(link => {
        const href = link.getAttribute("href");

        // Só links internos .php, sem target="_blank", sem #
        if (href && href.endsWith(".php") && !href.startsWith("http") && !link.target) {
            link.addEventListener("click", function (e) {
                // Não aplicar se for CTRL+click ou link de cancelar/apagar com confirm
                if (e.ctrlKey || e.metaKey || link.hasAttribute("onclick")) return;

                e.preventDefault();
                document.body.style.transition = "opacity 0.25s ease";
                document.body.style.opacity    = "0";

                setTimeout(() => {
                    window.location.href = href;
                }, 250);
            });
        }
    });

    // Fade in ao entrar na página
    document.body.style.opacity    = "0";
    document.body.style.transition = "opacity 0.3s ease";
    requestAnimationFrame(() => {
        requestAnimationFrame(() => {
            document.body.style.opacity = "1";
        });
    });


    // ============================================================
    // 6. TOOLTIP NOS BADGES E BOTÕES
    // Mostra uma pequena dica ao passar o rato
    // ============================================================
    document.querySelectorAll("[data-tooltip]").forEach(el => {
        el.style.position = "relative";
        el.style.cursor   = "default";

        el.addEventListener("mouseenter", function () {
            const tip = document.createElement("div");
            tip.className = "_tooltip";
            tip.textContent = el.dataset.tooltip;
            tip.style.cssText = `
                position: absolute;
                bottom: calc(100% + 8px);
                left: 50%;
                transform: translateX(-50%) scale(0.9);
                background: #1e293b;
                color: white;
                padding: 5px 10px;
                border-radius: 6px;
                font-size: 0.75rem;
                white-space: nowrap;
                z-index: 9999;
                pointer-events: none;
                opacity: 0;
                transition: opacity 0.2s ease, transform 0.2s ease;
                font-family: 'Poppins', sans-serif;
            `;
            el.appendChild(tip);
            requestAnimationFrame(() => {
                tip.style.opacity   = "1";
                tip.style.transform = "translateX(-50%) scale(1)";
            });
        });

        el.addEventListener("mouseleave", function () {
            const tip = el.querySelector("._tooltip");
            if (tip) tip.remove();
        });
    });


    // ============================================================
    // 7. CONFIRMAÇÃO VISUAL NOS BOTÕES DE AÇÃO DESTRUTIVA
    // (o onclick="return confirm()" continua a funcionar,
    //  mas também adiciona um tremor visual se cancelar)
    // ============================================================
    document.querySelectorAll(".btn-cancel, [data-confirm]").forEach(btn => {
        btn.addEventListener("click", function (e) {
            // Se o utilizador cancelar o confirm, anima o botão
            if (!window._confirmResult) {
                btn.style.animation = "shake 0.4s ease";
                setTimeout(() => btn.style.animation = "", 400);
            }
        });
    });


    // ============================================================
    // Dashboard: relógio, atualização live e bindings de cancel
    // ============================================================
    function atualizarRelogioPortugal() {
        const el = document.getElementById('hora-portugal');
        if (!el) return;
        try {
            el.textContent = new Date().toLocaleTimeString('pt-PT', { timeZone: 'Europe/Lisbon' });
        } catch (e) {
            el.textContent = new Date().toLocaleTimeString('pt-PT');
        }
    }

    function bindCancelButtons() {
        document.querySelectorAll('.btn-cancel-trigger').forEach(btn => {
            btn.replaceWith(btn.cloneNode(true));
        });

        document.querySelectorAll('.btn-cancel-trigger').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                currentCancelId = this.getAttribute('data-id');
                const bombaNum = this.getAttribute('data-bomba');
                document.getElementById('cancelBombaNum').textContent = bombaNum;
                document.getElementById('cancelModal').classList.add('active');
            });
        });
    }

    let currentCancelId = null;

    function atualizarDashboard() {
        fetch('api_dashboard_live.php')
            .then(r => r.json())
            .then(data => {
                for (let i = 1; i <= 4; i++) {
                    const hum    = data.sensores['sensor_' + i];
                    const on     = data.bombas['bomba_' + i] == 1;
                    const card   = document.getElementById('card-zona-' + i);
                    const humEl  = document.getElementById('humidade-' + i);
                    const barEl  = document.getElementById('barra-' + i);
                    const estEl  = document.getElementById('estado-bomba-' + i);
                    const btnEl  = document.getElementById('btn-bomba-' + i);
                    const inpEl  = document.getElementById('estado-input-' + i);
                    if (!humEl) continue;

                    humEl.innerHTML = hum + '<span class="unit">%</span>';
                    const cor = hum >= 60 ? '#0288d1' : hum >= 30 ? '#4caf50' : '#ef6c00';
                    barEl.style.width           = hum + '%';
                    barEl.style.backgroundColor = cor;
                    card.classList.toggle('alerta-seco', hum < 30);
                    estEl.className = 'bomba-estado ' + (on ? 'bomba-on' : 'bomba-off');
                    estEl.innerHTML = `<span class="dot"></span>${on ? 'Bomba Ligada' : 'Bomba Desligada'}`;
                    btnEl.className   = 'btn-pump ' + (on ? 'btn-off' : 'btn-on');
                    btnEl.innerHTML   = on ? '<i class="fas fa-stop"></i> Desligar Água' : '<i class="fas fa-play"></i> Ligar Água';
                    inpEl.value       = on ? 1 : 0;
                }

                try {
                    document.getElementById('ultima-atualizacao').textContent = new Date().toLocaleTimeString('pt-PT', { timeZone: 'Europe/Lisbon' });
                } catch (e) {
                    document.getElementById('ultima-atualizacao').textContent = new Date().toLocaleTimeString('pt-PT');
                }

                if (data.programacoes !== undefined) {
                    const tbody = document.getElementById('agendamentos-tbody');
                    if (!tbody) return;

                    if (!data.programacoes || data.programacoes.length === 0) {
                        tbody.innerHTML = `
                            <tr>
                                <td colspan="5" style="text-align:center; color:#bbb; padding:32px;">Nenhuma rega programada.</td>
                            </tr>`;
                    } else {
                        let rows = '';
                        data.programacoes.forEach(row => {
                            const executado = parseInt(row.executado) === 1;
                            const isOwner = !!row.is_owner;
                            const canCancel = !!row.can_cancel;
                            let dt = row.data_hora;
                            let dtObj = null;
                            try {
                                dtObj = new Date(dt.replace(' ', 'T'));
                            } catch (e) {
                                dtObj = new Date(dt);
                            }
                            const horario = dtObj && !isNaN(dtObj) ? dtObj.toLocaleString('pt-PT', { day:'2-digit', month:'2-digit', year:'numeric', hour:'2-digit', minute:'2-digit', second:'2-digit' }) : dt;

                            rows += `<tr>
                                <td><strong>Bomba ${row.bomba_id}</strong></td>
                                <td>${row.agendado_por || 'Sistema'}</td>
                                <td>${horario}</td>
                                <td>${row.duracao} seg</td>
                                <td>${executado ? '<span style="color:#2e7d32; font-weight:600;"><i class="fas fa-droplet"></i> A regar...</span>' : '<span style="color:#f39c12;"><i class="fas fa-hourglass"></i> Agendado</span>'}</td>
                                <td>${executado ? '<span style="color:#bbb; font-size:0.8rem;">Em curso</span>' : (canCancel ? `<button class="btn-cancel btn-cancel-trigger" data-id="${row.id}" data-bomba="${row.bomba_id}"><i class="fas fa-times"></i> Cancelar Rega</button>` : '<span style="color:#bbb; font-size:0.8rem;">Sem permissão</span>')}</td>
                            </tr>`;
                        });

                        tbody.innerHTML = rows;
                    }

                    bindCancelButtons();
                }
            })
            .catch(() => {});
    }

    // Inicializar relógio e atualizações
    setInterval(atualizarRelogioPortugal, 1000);
    atualizarRelogioPortugal();
    setInterval(atualizarDashboard, 5000);
    atualizarDashboard();

    // Inicializar listeners do modal
    document.getElementById('cancelCancelBtn')?.addEventListener('click', function() {
        document.getElementById('cancelModal').classList.remove('active');
        currentCancelId = null;
    });

    document.getElementById('confirmCancelBtn')?.addEventListener('click', function() {
        if (currentCancelId) {
            window.location.href = 'cancelar_agendamento.php?id=' + currentCancelId;
        }
    });

    document.getElementById('cancelModal')?.addEventListener('click', function(e) {
        if (e.target === this) {
            this.classList.remove('active');
            currentCancelId = null;
        }
    });

    // Bind initial buttons (se existirem)
    bindCancelButtons();

});