<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description"
        content="Abertura interativa de pacote de figurinhas no HK Web. Segure o botão esquerdo do mouse e arraste para cortar o pacote!">
    <title>Abrir Pacote - HK Web</title>
    <!-- Google Fonts for premium typography -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800&family=Plus+Jakarta+Sans:wght@300;400;600;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="../css_pages/card.css">
    <!-- Mudança: CSS do efeito de papel metalizado incluído -->
    <link rel="stylesheet" href="../Effects/metal_card.css">
</head>

<body>
    <!-- Canvas para desenhar o corte (Fullscreen) -->
    <canvas id="slash-canvas" class="slash-canvas" style="position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; z-index: 9000; pointer-events: none;"></canvas>

    <main class="page-container" id="slash-area">
        <div class="glass-card" id="main-glass-card">
            <h1 class="title" id="page-title">Abrir Pacote</h1>
            <p class="subtitle" id="page-subtitle">Segure o <strong>botão esquerdo do mouse</strong> e arraste para
                cortar o pacote!</p>

            <div class="pack-wrapper" id="pack-wrapper">
                <!-- Pacote principal -->
                <div class="interactive-pack" id="foil-pack">
                    <!-- Imagem do pacote inteiro -->
                    <div class="pack-visual" id="pack-visual">
                        <img src="../template/pack.webp" alt="Pacote de Figurinhas" class="pack-image" id="pack-img"
                            draggable="false">
                    </div>

                    <!-- Metades recortadas (Corte Vertical) -->
                    <div class="pack-half left" id="pack-half-left">
                        <img src="../template/pack.webp" alt="Pacote Esquerdo" class="pack-image-half"
                            draggable="false">
                    </div>
                    <div class="pack-half right" id="pack-half-right">
                        <img src="../template/pack.webp" alt="Pacote Direito" class="pack-image-half" draggable="false">
                    </div>

                    <!-- Metades recortadas (Corte Horizontal) -->
                    <div class="pack-half top" id="pack-half-top">
                        <img src="../template/pack.webp" alt="Pacote Topo" class="pack-image-half" draggable="false">
                    </div>
                    <div class="pack-half bottom" id="pack-half-bottom">
                        <img src="../template/pack.webp" alt="Pacote Base" class="pack-image-half" draggable="false">
                    </div>
                </div>

                <!-- Cartas Reveladas (ocultas por baixo) -->
                <!-- Mudança: Todas as cartas agora têm a mesma estrutura interna para suportar o efeito metalizado aleatório -->
                <div id="revealed-cards-container">
                    <div class="revealed-card stack-card" data-stack="2" style="z-index: 5;">
                        <div class="card-wrapper" style="width: 100%; height: 100%; max-width: none; margin: 0;">
                            <img src="../template/card.webp" alt="Carta 3" class="card-image" draggable="false" style="max-width: none; width: 100%; height: 100%; object-fit: contain;">
                        </div>
                    </div>
                    <div class="revealed-card stack-card" data-stack="1" style="z-index: 6;">
                        <div class="card-wrapper" style="width: 100%; height: 100%; max-width: none; margin: 0;">
                            <img src="../template/card.webp" alt="Carta 2" class="card-image" draggable="false" style="max-width: none; width: 100%; height: 100%; object-fit: contain;">
                        </div>
                    </div>
                    <div class="revealed-card stack-card" data-stack="0" style="z-index: 7;">
                        <div class="card-wrapper" style="width: 100%; height: 100%; max-width: none; margin: 0;">
                            <img src="../template/card.webp" alt="Carta 1" class="card-image" draggable="false" style="max-width: none; width: 100%; height: 100%; object-fit: contain;">
                        </div>
                    </div>
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
            // Mudança: Selecionando as 3 cartas ao invés de 1
            const revealedCards = document.querySelectorAll('.revealed-card');
            const btnReset = document.getElementById('btn-reset');
            const actionContainer = document.getElementById('action-container');
            const pageTitle = document.getElementById('page-title');
            const pageSubtitle = document.getElementById('page-subtitle');
            const slashArea = document.getElementById('slash-area');

            // Resize canvas to full screen
            function resizeCanvas() {
                canvas.width = window.innerWidth;
                canvas.height = window.innerHeight;
            }
            window.addEventListener('resize', resizeCanvas);
            resizeCanvas();

            let isDrawing = false;
            let points = [];
            let isCutTriggered = false;

            // Rastreamento 3D do mouse
            if (wrapper) {
                wrapper.addEventListener('mousemove', (e) => {
                    const rect = wrapper.getBoundingClientRect();
                    const x = (e.clientX - rect.left) / rect.width - 0.5;
                    const y = (e.clientY - rect.top) / rect.height - 0.5;

                    wrapper.style.setProperty('--mouse-x', x.toFixed(3));
                    wrapper.style.setProperty('--mouse-y', y.toFixed(3));
                });

                wrapper.addEventListener('mouseleave', () => {
                    wrapper.style.setProperty('--mouse-x', '0');
                    wrapper.style.setProperty('--mouse-y', '0');
                });
            }

            // Desabilitar menu de contexto do botão direito no pacote
            foilPack.addEventListener('contextmenu', (e) => {
                e.preventDefault();
            });

            // Iniciar o rastro de corte
            slashArea.addEventListener('mousedown', (e) => {
                if (e.target.closest('button') || e.target.closest('a')) return;
                if (isCutTriggered) return;
                // Botão esquerdo = 0
                if (e.button === 0) {
                    isDrawing = true;
                    points = [];
                    canvas.style.opacity = '1';

                    points.push({ x: e.clientX, y: e.clientY });
                }
            });

            // Capturar movimento do mouse para desenhar o rastro
            document.addEventListener('mousemove', (e) => {
                if (!isDrawing || isCutTriggered) return;

                points.push({ x: e.clientX, y: e.clientY });
                drawSlash();
            });

            // Finalizar o rastro de corte
            document.addEventListener('mouseup', (e) => {
                if (!isDrawing || isCutTriggered) return;
                isDrawing = false;

                const cutResult = isEdgeToEdge();
                if (cutResult.isCut) {
                    triggerCut(cutResult.isHorizontal);
                } else {
                    fadeSlash();
                }
            });

            // Lógica tolerante (sensível) para detectar corte de borda a borda e direção
            function isEdgeToEdge() {
                if (points.length < 3) return { isCut: false };

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

                // Considerando o tamanho da tela, 250px é um gesto suficiente para abrir o pacote
                if (swipeWidth > 250 || swipeHeight > 250) {
                    // Direção baseada na maior variação de movimento (horizontal ou vertical)
                    return { isCut: true, isHorizontal: swipeWidth > swipeHeight };
                }
                
                return { isCut: false };
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

            function triggerCut(isHorizontal) {
                isCutTriggered = true;
                ctx.clearRect(0, 0, canvas.width, canvas.height);

                // Adiciona a classe de animação do corte baseada na direção
                if (isHorizontal) {
                    foilPack.classList.add('animate-cut-horizontal');
                } else {
                    foilPack.classList.add('animate-cut-vertical');
                }

                // Revelação das cartas
                setTimeout(() => {
                    // Mudança: Escolhe aleatoriamente uma das 3 cartas para ser a metalizada neste pacote
                    revealedCards.forEach(card => {
                        const wrapper = card.querySelector('.card-wrapper');
                        if (wrapper) wrapper.classList.remove('metal-card');
                    });
                    
                    const randomIndex = Math.floor(Math.random() * revealedCards.length);
                    const chosenWrapper = revealedCards[randomIndex].querySelector('.card-wrapper');
                    if (chosenWrapper) chosenWrapper.classList.add('metal-card');

                    // Mudança: Mostrar as 3 cartas e adicionar o evento de clique para jogá-las para fora
                    revealedCards.forEach((card, index) => {
                        card.classList.add('revealed');
                        
                        // Evento para jogar a carta para fora ao clicar
                        card.onclick = function() {
                            const direction = Math.random() > 0.5 ? 1 : -1; // Direita ou Esquerda
                            
                            // Mudança: Remove o drop-shadow instantaneamente para não projetar sombra nas cartas de baixo
                            const img = this.querySelector('.card-image');
                            if(img) {
                                img.style.filter = 'none';
                                img.style.transition = 'none'; // Remove delay da transição de filtro
                            }

                            this.style.transition = 'transform 0.8s cubic-bezier(0.25, 1, 0.5, 1), opacity 0.6s ease';
                            this.style.transform = `translate(${direction * 800}px, -100px) rotate(${direction * 360}deg) scale(0.5)`;
                            this.style.opacity = '0';
                            this.style.pointerEvents = 'none'; // Evita múltiplos cliques
                            
                            // Mudança: Animar as cartas de trás para frente
                            let currentStack = parseInt(this.getAttribute('data-stack'));
                            this.removeAttribute('data-stack'); // Tira esta carta da lógica de pilha
                            
                            revealedCards.forEach(otherCard => {
                                if (otherCard.hasAttribute('data-stack')) {
                                    let otherStack = parseInt(otherCard.getAttribute('data-stack'));
                                    if (otherStack > currentStack) {
                                        let newStack = otherStack - 1;
                                        otherCard.setAttribute('data-stack', newStack);
                                        // Habilita clique na nova carta do topo
                                        if (newStack === 0) {
                                            otherCard.style.pointerEvents = 'auto';
                                        }
                                    }
                                }
                            });
                        };
                    });

                    pageTitle.textContent = "Pacote Aberto!";
                    pageSubtitle.innerHTML = "Clique nas cartas para descartá-las!";

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
                    foilPack.classList.remove('animate-cut-vertical', 'animate-cut-horizontal');
                    // Mudança: Resetar estado de todas as 3 cartas
                    revealedCards.forEach((card, index) => {
                        card.classList.remove('revealed');
                        card.style.transition = ''; // Limpa a transição do clique
                        card.style.transform = ''; // Volta ao transform do CSS original
                        card.style.opacity = '';
                        card.style.pointerEvents = '';
                        
                        // Restaura a ordem da pilha
                        card.setAttribute('data-stack', 2 - index);
                        
                        // Restaura o filtro da imagem (sombra)
                        const img = card.querySelector('.card-image');
                        if(img) {
                            img.style.filter = '';
                            img.style.transition = '';
                        }
                    });
                    
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