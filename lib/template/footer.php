<div id="copyright">
		<div style="margin:0 auto;">
			<span class="light">© 2021 RG Laboratorio Dental</span>
			<div class="footer-links">
                <?php foreach ($cfg['footerLinks'] as $key => $val): ?>
					<a href="<?php echo $val ?>"><span><?php echo $key ?></span></a><span> |</span>
				<?php endforeach ?>
                <!-- Installation dependend links -->
                <?php if (false === empty($cfg['installation_done'])) {
                    echo '<a href="tos.php" target="_blank" rel="noopener noreferrer">' . t('TOS') . '</a>';
                } ?>
            </div>
		</div>
</div>
<div id="jyraphe" title="<?php echo t('POWERED_BY') ?>" onclick="javascript:window.open('https://gitlab.com/mojo42/Jirafeau');">
</div>
</body>
</html>
