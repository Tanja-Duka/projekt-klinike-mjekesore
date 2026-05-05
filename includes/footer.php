<footer class="site-footer">
    <div class="footer-main">
        <div class="container footer-grid">

            <!-- Kolona 1: Rreth klinikës -->
            <div class="footer-col">
                <div class="footer-logo">
                    <div class="navbar-logo-icon">&#43;</div>
                    <div class="navbar-logo-text">
                        <strong>Vitanova</strong>
                        <span>Clinic</span>
                    </div>
                </div>
                <p class="footer-desc">Kujdes i plotë shëndetësor për ju dhe familjen tuaj — në çdo hap të jetës.</p>
            </div>

            <!-- Kolona 2: Linqe të shpejta -->
            <div class="footer-col">
                <h4 class="footer-heading">Linqe të Shpejta</h4>
                <ul class="footer-links">
                    <li><a href="<?= BASE_URL ?>/public/home.php">Home</a></li>
                    <li><a href="<?= BASE_URL ?>/public/services.php">Shërbimet</a></li>
                    <li><a href="<?= BASE_URL ?>/public/doctors.php">Mjekët</a></li>
                    <li><a href="<?= BASE_URL ?>/public/about.php">Rreth Nesh</a></li>
                    <li><a href="<?= BASE_URL ?>/public/contact.php">Kontakt</a></li>
                </ul>
            </div>

            <!-- Kolona 3: Kontakt -->
            <div class="footer-col">
                <h4 class="footer-heading">Kontakt</h4>
                <ul class="footer-contact">
                    <li><span>&#128205;</span> Rruga e Dibrës, Tiranë</li>
                    <li><span>&#128222;</span> +355 68 123 4567</li>
                    <li><span>&#9993;</span> info@vitanova.al</li>
                </ul>
            </div>

            <!-- Kolona 4: Orari -->
            <div class="footer-col">
                <h4 class="footer-heading">Orari i Punës</h4>
                <ul class="footer-schedule">
                    <li><span>E Hënë – E Premte</span><span>08:00 – 18:00</span></li>
                    <li><span>E Shtunë</span><span>08:00 – 14:00</span></li>
                    <li><span>E Diel</span><span>Mbyllur</span></li>
                </ul>
            </div>

        </div>
    </div>

    <!-- Bar poshtë -->
    <div class="footer-bottom">
        <div class="container footer-bottom-inner">
            <p>&copy; <?= date('Y') ?> <?= APP_NAME ?>. Të gjitha të drejtat e rezervuara.</p>
            <div class="footer-social">
                <a href="#" aria-label="Facebook">&#9670;</a>
                <a href="#" aria-label="Instagram">&#9670;</a>
                <a href="#" aria-label="LinkedIn">&#9670;</a>
            </div>
        </div>
    </div>
</footer>

<!-- jQuery (i nevojshëm për reserve.js dhe search.js) -->
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

<style>
/* Footer stilet (inline për të mos çuar skedar shtesë) */
.site-footer { background: var(--color-footer); color: rgba(255,255,255,0.75); }
.footer-main { padding: 60px 0 40px; }
.footer-grid {
    display: grid;
    grid-template-columns: 1.4fr 1fr 1fr 1fr;
    gap: 40px;
}
.footer-logo { display: flex; align-items: center; gap: 10px; margin-bottom: 14px; }
.footer-logo .navbar-logo-icon { background: var(--color-primary); }
.footer-logo .navbar-logo-text strong { color: #fff; }
.footer-logo .navbar-logo-text span   { color: rgba(255,255,255,0.45); }
.footer-desc { font-size: 0.88rem; line-height: 1.6; color: rgba(255,255,255,0.55); margin: 0; }
.footer-heading { color: #fff; font-size: 0.9rem; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 16px; }
.footer-links li { margin-bottom: 8px; }
.footer-links a  { color: rgba(255,255,255,0.6); font-size: 0.88rem; transition: var(--transition); }
.footer-links a:hover { color: #fff; }
.footer-contact li { display: flex; align-items: flex-start; gap: 10px; font-size: 0.88rem; margin-bottom: 10px; color: rgba(255,255,255,0.6); }
.footer-contact span { color: var(--color-cta); font-size: 1rem; flex-shrink: 0; }
.footer-schedule { list-style: none; }
.footer-schedule li { display: flex; justify-content: space-between; font-size: 0.85rem; color: rgba(255,255,255,0.6); padding: 5px 0; border-bottom: 1px solid rgba(255,255,255,0.06); }
.footer-bottom { border-top: 1px solid rgba(255,255,255,0.08); padding: 18px 0; }
.footer-bottom-inner { display: flex; align-items: center; justify-content: space-between; }
.footer-bottom p  { margin: 0; font-size: 0.84rem; color: rgba(255,255,255,0.45); }
.footer-social { display: flex; gap: 12px; }
.footer-social a { color: rgba(255,255,255,0.45); font-size: 0.9rem; transition: var(--transition); }
.footer-social a:hover { color: var(--color-cta); }
@media (max-width: 900px) { .footer-grid { grid-template-columns: 1fr 1fr; gap: 28px; } }
@media (max-width: 560px) { .footer-grid { grid-template-columns: 1fr; } .footer-bottom-inner { flex-direction: column; gap: 12px; text-align: center; } }
</style>

