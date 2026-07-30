<?php
// includes/footer.php
if (!defined('SITE_URL')) {
    define('SITE_URL', '/jms/');
}
?>
<footer class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-10 pb-6 text-sm text-gray-500 mt-8">
    <!-- Footer Content with Glass Effect -->
    <div class="bg-white/80 backdrop-blur-sm rounded-2xl p-8 md:p-10 shadow-lg border border-white/30">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
            <div>
                <h5 class="font-semibold text-[#0b2b3f]">About TIRP</h5>
                <ul class="mt-2 space-y-1">
                    <li><a href="<?= SITE_URL ?>?page=about" class="hover:text-[#0b2b3f] transition-colors">Aims & scope</a></li>
                    <li><a href="<?= SITE_URL ?>?page=editorial" class="hover:text-[#0b2b3f] transition-colors">Editorial board</a></li>
                    <li><a href="<?= SITE_URL ?>?page=publication-ethics" class="hover:text-[#0b2b3f] transition-colors">Publication ethics</a></li>
                    <li><a href="<?= SITE_URL ?>?page=contact" class="hover:text-[#0b2b3f] transition-colors">Contact us</a></li>
                </ul>
            </div>
            <div>
                <h5 class="font-semibold text-[#0b2b3f]">For authors</h5>
                <ul class="mt-2 space-y-1">
                    <li><a href="<?= SITE_URL ?>?page=author-guidelines" class="hover:text-[#0b2b3f] transition-colors">Instructions</a></li>
                    <li><a href="<?= SITE_URL ?>?page=submit" class="hover:text-[#0b2b3f] transition-colors">Submit</a></li>
                    <li><a href="<?= SITE_URL ?>?page=author-guidelines" class="hover:text-[#0b2b3f] transition-colors">Author guidelines</a></li>
                    <li><a href="<?= SITE_URL ?>?page=faq" class="hover:text-[#0b2b3f] transition-colors">FAQ</a></li>
                </ul>
            </div>
            <div>
                <h5 class="font-semibold text-[#0b2b3f]">For reviewers</h5>
                <ul class="mt-2 space-y-1">
                    <li><a href="<?= SITE_URL ?>?page=reviewer-guidelines" class="hover:text-[#0b2b3f] transition-colors">Reviewer guidelines</a></li>
                    <li><a href="<?= SITE_URL ?>?page=login" class="hover:text-[#0b2b3f] transition-colors">Login to review</a></li>
                    <li><a href="<?= SITE_URL ?>?page=reviewer-faq" class="hover:text-[#0b2b3f] transition-colors">Reviewer FAQ</a></li>
                </ul>
            </div>
            <div>
                <h5 class="font-semibold text-[#0b2b3f]">Contact</h5>
                <p class="mt-2">P.O. Box 1541, KCMC, Moshi</p>
                <p class="text-xs">+255 763 872 771</p>
                <p class="text-xs">info@lightmantz.com</p>
                <div class="mt-3 flex gap-3">
                    <a href="#" class="text-gray-400 hover:text-[#0b2b3f] transition-colors"><i class="fab fa-facebook"></i></a>
                    <a href="#" class="text-gray-400 hover:text-[#0b2b3f] transition-colors"><i class="fab fa-twitter"></i></a>
                    <a href="#" class="text-gray-400 hover:text-[#0b2b3f] transition-colors"><i class="fab fa-linkedin"></i></a>
                    <a href="#" class="text-gray-400 hover:text-[#0b2b3f] transition-colors"><i class="fab fa-youtube"></i></a>
                </div>
            </div>
        </div>
        <div class="mt-8 pt-4 border-t border-gray-200 flex flex-col md:flex-row justify-between items-center gap-2 text-xs text-gray-400">
            <span>&copy; 2026 Tanzania Journal of Rehabilitation Practice · ISSN <?= function_exists('getSetting') ? getSetting('journal_issn') : '1234-5678' ?></span>
            <span class="flex items-center gap-4">
                <i class="fas fa-lock text-emerald-600"></i> HTTPS · DOI · Open Access
                <a href="<?= SITE_URL ?>?page=privacy-policy" class="hover:text-[#0b2b3f] transition-colors">Privacy Policy</a>
                <a href="<?= SITE_URL ?>?page=terms" class="hover:text-[#0b2b3f] transition-colors">Terms</a>
            </span>
        </div>
    </div>
</footer>
</body>
</html>