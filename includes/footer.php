<footer class="site-footer">
    <div class="container">
        <div class="footer-grid">

            <!-- Kolona 1: Brand -->
            <div class="footer-brand">
                <h3>Vitanova</h3>
                <p>Kujdes i plotë shëndetësor për ju dhe familjen tuaj — në çdo hap të jetës.</p>
            </div>

            <!-- Kolona 2: Linqe të shpejta -->
            <div class="footer-col">
                <h5>Navigim</h5>
                <ul>
                    <li><a href="<?= BASE_URL ?>/public/home.php">Home</a></li>
                    <li><a href="<?= BASE_URL ?>/public/services.php">Shërbimet</a></li>
                    <li><a href="<?= BASE_URL ?>/public/doctors.php">Mjekët</a></li>
                    <li><a href="<?= BASE_URL ?>/public/about.php">Rreth Nesh</a></li>
                    <li><a href="<?= BASE_URL ?>/public/contact.php">Kontakt</a></li>
                </ul>
            </div>

            <!-- Kolona 3: Kontakt -->
            <div class="footer-col">
                <h5>Kontakt</h5>
                <ul>
                    <li class="footer-contact-item"><span>&#128205;</span> Rr. Fehmi Agani, Prishtinë</li>
                    <li class="footer-contact-item"><span>&#128222;</span> +383 44 000 000</li>
                    <li class="footer-contact-item"><span>&#9993;</span> info@vitanova.com</li>
                </ul>
            </div>

            <!-- Kolona 4: Orari -->
            <div class="footer-col">
                <h5>Orari i Punës</h5>
                <ul class="footer-schedule">
                    <li><span>E Hënë – E Premte</span><span>08:00 – 20:00</span></li>
                    <li><span>E Shtunë</span><span>08:00 – 14:00</span></li>
                    <li><span>E Diel</span><span>Mbyllur</span></li>
                </ul>
            </div>

        </div>

        <div class="footer-bottom">
            <p>&copy; <?= date('Y') ?> <?= APP_NAME ?>. Të gjitha të drejtat e rezervuara.</p>
            <div class="footer-social">
                <a href="#" aria-label="Facebook">&#9670;</a>
                <a href="#" aria-label="Instagram">&#9670;</a>
                <a href="#" aria-label="LinkedIn">&#9670;</a>
            </div>
        </div>
    </div>
</footer>

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<!-- JS i aplikacionit -->
<script src="<?= BASE_URL ?>/assets/js/main.js"></script>
<script src="<?= BASE_URL ?>/assets/js/validate.js"></script>
<?php if (!empty($extraJs) && is_array($extraJs)): ?>
    <?php foreach ($extraJs as $js): ?>
        <script src="<?= BASE_URL ?>/assets/js/<?= e($js) ?>"></script>
    <?php endforeach; ?>
<?php endif; ?>
</body>
</html>
