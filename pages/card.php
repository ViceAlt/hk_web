<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Abertura interativa de pacote de figurinhas no HK Web. Segure o botão esquerdo do mouse e arraste para cortar o pacote!">
    <title>Abrir Pacote - HK Web</title>
    <!-- Google Fonts for premium typography -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800&family=Plus+Jakarta+Sans:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css_pages/card.css">
</head>
<body>
    <main class="page-container">
        <div class="glass-card" id="main-glass-card">
            <h1 class="title" id="page-title">Abrir Pacote</h1>
            <p class="subtitle" id="page-subtitle">Segure o <strong>botão esquerdo do mouse</strong> e arraste para cortar o pacote!</p>
            
            <div class="pack-wrapper" id="pack-wrapper">
                <!-- Pacote principal -->
                <div class="interactive-pack" id="foil-pack">
                    <!-- Canvas para desenhar o corte -->
                    <canvas id="slash-canvas" class="slash-canvas" width="230" height="320"></canvas>
                    
                    <!-- Imagem do pacote inteiro -->
                    <div class="pack-visual" id="pack-visual">
                        <img src="pack.webp" alt="Pacote de Figurinhas" class="pack-image" id="pack-img" draggable="false">
                    </div>

                    <!-- Metades recortadas (usadas na animação de corte) -->
                    <div class="pack-half left" id="pack-half-left">
                        <img src="pack.webp" alt="Pacote Esquerdo" class="pack-image-half" draggable="false">
                    </div>
                    <div class="pack-half right" id="pack-half-right">
                        <img src="pack.webp" alt="Pacote Direito" class="pack-image-half" draggable="false">
                    </div>
                </div>

                <!-- Carta Revelada (oculta por baixo) -->
                <div class="revealed-card" id="revealed-card">
                    <div class="card-glow-burst"></div>
                    <img src="card.webp" alt="Carta Revelada" class="card-image" draggable="false">
                </div>

                <!-- Sombra do pacote -->
                <div class="pack-shadow-wrapper" id="pack-shadow-wrap">
                    <div class="pack-shadow" id="pack-shadow"></div>
                </div>
            </div>

            <div class="action-container" id="action-container" style="display: none;">
                <button id="btn-reset" class="premium-button">Abrir Novo Pacote</button>
            </div>
            <div class="navigation-container">
                <a href="index.php" id="btn-back" class="link-back">Voltar para o Início</a>
            </div>
        </div>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const wrapper = document.getElementById('pack-wrapper');
            const foilPack = document.getElementById('foil-pack');
            const canvas = document.getElementById('slash-canvas');
            const ctx = canvas.getContext('2d');
            const revealedCard = document.getElementById('revealed-card');
            const packShadowWrap = document.getElementById('pack-shadow-wrap');
            const btnReset = document.getElementById('btn-reset');
            const actionContainer = document.getElementById('action-container');
            const pageTitle = document.getElementById('page-title');
            const pageSubtitle = document.getElementById('page-subtitle');
            
            let isDrawing = false;
            let points = [];
            let isCutTriggered = false;

            // Rastreamento 3D do mouse
            if (wrapper) {
                wrapper.addEventListener('mousemove', (e) => {
                    if (isCutTriggered) return;
                    const rect = wrapper.getBoundingClientRect();
                    const x = (e.clientX - rect.left) / rect.width - 0.5;
                    const y = (e.clientY - rect.top) / rect.height - 0.5;
                    
                    wrapper.style.setProperty('--mouse-x', x.toFixed(3));
                    wrapper.style.setProperty('--mouse-y', y.toFixed(3));
                });
                
                wrapper.addEventListener('mouseleave', () => {
                    if (isCutTriggered) return;
                    wrapper.style.setProperty('--mouse-x', '0');
                    wrapper.style.setProperty('--mouse-y', '0');
                });
            }

            // Desabilitar menu de contexto do botão direito no pacote
            foilPack.addEventListener('contextmenu', (e) => {
                e.preventDefault();
            });

            // Iniciar o rastro de corte
            foilPack.addEventListener('mousedown', (e) => {
                if (isCutTriggered) return;
                // Botão esquerdo = 0
                if (e.button === 0) {
                    isDrawing = true;
                    points = [];
                    canvas.style.opacity = '1';
                    
                    const rect = foilPack.getBoundingClientRect();
                    const startX = e.clientX - rect.left;
                    const startY = e.clientY - rect.top;
                    
                    points.push({ x: startX, y: startY });
                }
            });

            // Capturar movimento do mouse para desenhar o rastro
            document.addEventListener('mousemove', (e) => {
                if (!isDrawing || isCutTriggered) return;
                
                const rect = foilPack.getBoundingClientRect();
                const x = e.clientX - rect.left;
                const y = e.clientY - rect.top;
                
                points.push({ x, y });
                drawSlash();
            });

            // Finalizar o rastro de corte
            document.addEventListener('mouseup', (e) => {
                if (!isDrawing || isCutTriggered) return;
                isDrawing = false;
                
                if (isEdgeToEdge()) {
                    triggerCut();
                } else {
                    fadeSlash();
                }
            });

            // Lógica tolerante (sensível) para detectar corte de borda a borda
            function isEdgeToEdge() {
                if (points.length < 3) return false;

                const w = canvas.width;
                const h = canvas.height;

                let minX = points[0].x, maxX = points[0].x;
                let minY = points[0].y, maxY = points[0].y;

                for (let p of points) {
                    if (p.x < minX) minX = p.x;
                    if (p.x > maxX) maxX = p.x;
                    if (p.y < minY) minY = p.y;
                    if (p.y > maxY) maxY = p.y;
                }

                const swipeWidth = maxX - minX;
                const swipeHeight = maxY - minY;

                // Se cobrir mais de 65% de toda a largura ou altura, é considerado um corte de lado a lado
                return (swipeWidth > w * 0.65) || (swipeHeight > h * 0.65);
            }

            function drawSlash() {
                ctx.clearRect(0, 0, canvas.width, canvas.height);
                if (points.length < 2) return;
                
                // Desenhar sombra externa brilhante (glow)
                ctx.beginPath();
                ctx.moveTo(points[0].x, points[0].y);
                for (let i = 1; i < points.length; i++) {
                    ctx.lineTo(points[i].x, points[i].y);
                }
                ctx.strokeStyle = 'rgba(6, 182, 212, 0.5)';
                ctx.lineWidth = 12;
                ctx.lineCap = 'round';
                ctx.lineJoin = 'round';
                ctx.shadowBlur = 12;
                ctx.shadowColor = '#06b6d4';
                ctx.stroke();

                // Desenhar linha interna branca sólida
                ctx.beginPath();
                ctx.moveTo(points[0].x, points[0].y);
                for (let i = 1; i < points.length; i++) {
                    ctx.lineTo(points[i].x, points[i].y);
                }
                ctx.strokeStyle = '#ffffff';
                ctx.lineWidth = 4;
                ctx.shadowBlur = 0; // reset glow
                ctx.stroke();
            }

            function fadeSlash() {
                let opacity = 1.0;
                function fade() {
                    opacity -= 0.08;
                    if (opacity <= 0) {
                        ctx.clearRect(0, 0, canvas.width, canvas.height);
                        canvas.style.opacity = '1';
                    } else {
                        canvas.style.opacity = opacity;
                        requestAnimationFrame(fade);
                    }
                }
                fade();
            }

            function triggerCut() {
                isCutTriggered = true;
                ctx.clearRect(0, 0, canvas.width, canvas.height);
                
                // Reseta a inclinação 3D para fazer o corte limpo
                wrapper.style.setProperty('--mouse-x', '0');
                wrapper.style.setProperty('--mouse-y', '0');

                // Adiciona a classe de animação do corte
                foilPack.classList.add('animate-cut');
                if (packShadowWrap) {
                    packShadowWrap.style.opacity = '0';
                    packShadowWrap.style.transform = 'scale(0)';
                }

                // Revelação da carta
                setTimeout(() => {
                    revealedCard.classList.add('revealed');
                    pageTitle.textContent = "Pacote Aberto!";
                    pageSubtitle.innerHTML = "Parabéns, você tirou uma carta incrível!";
                    
                    // Mostrar botão para abrir outro pacote
                    setTimeout(() => {
                        actionContainer.style.display = 'flex';
                        setTimeout(() => actionContainer.classList.add('fade-in'), 50);
                    }, 500);
                }, 400);
            }

            // Resetar a página para o estado inicial
            if (btnReset) {
                btnReset.addEventListener('click', () => {
                    isCutTriggered = false;
                    foilPack.classList.remove('animate-cut');
                    revealedCard.classList.remove('revealed');
                    if (packShadowWrap) {
                        packShadowWrap.style.opacity = '1';
                        packShadowWrap.style.transform = 'translate(calc(var(--mouse-x) * 45px), calc(var(--mouse-y) * 12px)) scale(1)';
                    }
                    actionContainer.classList.remove('fade-in');
                    setTimeout(() => {
                        actionContainer.style.display = 'none';
                    }, 400);
                    
                    pageTitle.textContent = "Abrir Pacote";
                    pageSubtitle.innerHTML = "Segure o <strong>botão esquerdo do mouse</strong> e arraste para cortar o pacote!";
                    ctx.clearRect(0, 0, canvas.width, canvas.height);
                });
            }
        });
    </script>
</body>
</html>
