
    <h1>Glücksrad</h1>
    <div id="wheel">
        <div class="arrow">↑</div>
    </div>
    <button id="spin">Drehen</button>

    <script>
        let isSpinning = false;

        document.getElementById('spin').addEventListener('click', function() {
            if (isSpinning) return; // Verhindern, dass der Button mehrfach geklickt wird während das Rad dreht.

            isSpinning = true;

            let wheel = document.getElementById('wheel');
            let randomDeg = Math.floor(Math.random() * 360) + 720; // Min. 2 volle Drehungen, um das Rad interessant zu machen.

            // Animation hinzufügen
            wheel.style.transition = 'transform 4s ease-out';
            wheel.style.transform = `rotate(${randomDeg}deg)`;

            // Nach der Animation, zurücksetzen
            setTimeout(function() {
                isSpinning = false;
                let result = randomDeg % 360; // Restwert des Drehwinkels
                alert('Das Glücksrad landete auf ' + result + '°!');
                wheel.style.transition = ''; // Übergang zurücksetzen
                wheel.style.transform = ''; // Position zurücksetzen
            }, 4000); // Die Zeit muss der Dauer der Animation entsprechen
        });
    </script>