<?php
/**
 * Mall för förstasidan. Samma HTML och klasser (cvfs-*) som prototypen.
 *
 * Tillgängligt: $lang, $s (inställningar), $guided (bool), $search (AI-sökets HTML),
 * $cards['guided'|'popular'|'news'], $posts (blogginlägg), $map (kartans HTML eller '').
 *
 * @package cyklavandra-forstasida
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$cvfs_head = function ( $title, $link_text, $url ) {
	?>
	<div class="cvfs-head">
		<div>
			<h2><?php echo esc_html( $title ); ?></h2>
		</div>
		<?php if ( $link_text && $url ) : ?>
			<div class="cvfs-headlink"><a href="<?php echo esc_url( $url ); ?>"><?php echo esc_html( $link_text ); ?></a></div>
		<?php endif; ?>
	</div>
	<?php
};

$cvfs_card = function ( $c ) {
	?>
	<div class="cvfs-card">
		<?php if ( $c['image'] ) : ?>
			<div class="cvfs-img"><img src="<?php echo esc_url( $c['image'] ); ?>" alt="<?php echo esc_attr( $c['alt'] ); ?>" loading="lazy" decoding="async" /></div>
		<?php endif; ?>
		<div class="cvfs-card-b">
			<?php if ( $c['meta'] ) : ?>
				<div class="cvfs-meta"><?php echo esc_html( $c['meta'] ); ?></div>
			<?php endif; ?>
			<h3><a class="cvfs-stretch" href="<?php echo esc_url( $c['url'] ); ?>"><?php echo esc_html( $c['title'] ); ?></a></h3>
			<?php if ( $c['price'] ) : ?>
				<div class="cvfs-price"><?php echo esc_html( $c['price'] ); ?></div>
			<?php endif; ?>
		</div>
	</div>
	<?php
};

$cvfs_images  = array_values( array_filter( array_map( 'esc_url_raw', (array) $s['hero']['images'] ) ) );
$cvfs_mobile  = array_values( array_filter( array_map( 'esc_url_raw', (array) $s['hero']['images_mobile'] ) ) );
?>
<div class="cvfs<?php echo $map ? ' cvfs-has-map' : ''; ?>" lang="<?php echo esc_attr( $lang ); ?>">

	<?php /* 1. Hero med AI-sökning */ ?>
	<div class="cvfs-hero">
		<div class="cvfs-hero-in">
			<h1><?php echo esc_html( $s['hero']['title'] ); ?></h1>
			<?php if ( $s['hero']['lead'] ) : ?>
				<p class="cvfs-lead"><?php echo esc_html( $s['hero']['lead'] ); ?></p>
			<?php endif; ?>
			<?php echo $search; // phpcs:ignore WordPress.Security.EscapeOutput -- HTML från AI-sökets shortcode. ?>
		</div>
	</div>
	<?php if ( $cvfs_images || $cvfs_mobile ) : ?>
		<?php /* Mobil (samma gräns som CSS:en, 700 px) får egna bilder om sådana finns. */ ?>
		<script>(function(){var d=<?php echo wp_json_encode( $cvfs_images ); ?>,m=<?php echo wp_json_encode( $cvfs_mobile ); ?>,a=(m.length&&window.matchMedia&&window.matchMedia("(max-width:700px)").matches)||!d.length?m:d,e=document.querySelector(".cvfs-hero");if(e&&a.length){e.style.setProperty("--cvfs-hero-img","url("+a[Math.floor(Math.random()*a.length)]+")");}})();</script>
	<?php endif; ?>

	<?php /* 2. Tre rutor + 3. faktarad */ ?>
	<div class="cvfs-wrap">
		<div class="cvfs-paths">
			<?php
			$cvfs_paths = array(
				'guided' => 'cvfs-g',
				'bike'   => 'cvfs-s',
				'hike'   => 'cvfs-v',
			);
			foreach ( $cvfs_paths as $cvfs_key => $cvfs_class ) :
				if ( 'guided' === $cvfs_key && ! $guided ) {
					continue;
				}
				$cvfs_p = $s['paths'][ $cvfs_key ];
				?>
				<div class="cvfs-path <?php echo esc_attr( $cvfs_class ); ?>">
					<h2><a class="cvfs-stretch" href="<?php echo esc_url( $cvfs_p['url'] ); ?>"><?php echo esc_html( $cvfs_p['title'] ); ?></a></h2>
					<p><?php echo esc_html( $cvfs_p['text'] ); ?></p>
					<div class="cvfs-more"><?php echo esc_html( $cvfs_p['link_text'] ); ?></div>
				</div>
			<?php endforeach; ?>
		</div>
		<div class="cvfs-facts">
			<?php
			foreach ( $s['facts'] as $cvfs_f ) :
				if ( '' === $cvfs_f['title'] && '' === $cvfs_f['sub'] ) {
					continue;
				}
				?>
				<div class="cvfs-fact">
					<div class="cvfs-fact-t"><?php echo esc_html( $cvfs_f['title'] ); ?></div>
					<div class="cvfs-fact-s"><?php echo esc_html( $cvfs_f['sub'] ); ?></div>
				</div>
			<?php endforeach; ?>
		</div>
	</div>

	<?php /* 4. Aktuella guidade resor (bara när guidade resor visas) */ ?>
	<?php if ( $guided && $cards['guided'] ) : ?>
		<div class="cvfs-band">
			<div class="cvfs-wrap cvfs-sec">
				<?php $cvfs_head( $s['guided']['title'], $s['guided']['link_text'], $s['guided']['url'] ); ?>
				<div class="cvfs-grid">
					<?php array_map( $cvfs_card, $cards['guided'] ); ?>
				</div>
			</div>
		</div>
	<?php endif; ?>

	<?php /* 5. Populära resor på egen hand */ ?>
	<?php if ( $cards['popular'] ) : ?>
		<div class="cvfs-wrap cvfs-sec">
			<?php $cvfs_head( $s['popular']['title'], $s['popular']['link_text'], $s['popular']['url'] ); ?>
			<div class="cvfs-grid">
				<?php array_map( $cvfs_card, $cards['popular'] ); ?>
			</div>
		</div>
	<?php endif; ?>

	<?php /* 6. Nyheter för säsongen */ ?>
	<?php if ( $cards['news'] ) : ?>
		<div class="cvfs-band">
			<div class="cvfs-wrap cvfs-sec">
				<?php $cvfs_head( $s['news']['title'], $s['news']['link_text'], $s['news']['url'] ); ?>
				<div class="cvfs-grid cvfs-grid4">
					<?php array_map( $cvfs_card, $cards['news'] ); ?>
				</div>
			</div>
		</div>
	<?php endif; ?>

	<?php /* 7. Nyhetsbrev (senaste inläggen) */ ?>
	<?php if ( $posts ) : ?>
		<div class="cvfs-wrap cvfs-sec cvfs-blogsec">
			<?php $cvfs_head( $s['blog']['title'], $s['blog']['link_text'], $s['blog']['url'] ); ?>
			<div class="cvfs-grid cvfs-grid3">
				<?php foreach ( $posts as $cvfs_post ) : ?>
					<div class="cvfs-card">
						<?php if ( $cvfs_post['image'] ) : ?>
							<div class="cvfs-img"><img src="<?php echo esc_url( $cvfs_post['image'] ); ?>" alt="" loading="lazy" decoding="async" /></div>
						<?php endif; ?>
						<div class="cvfs-card-b">
							<div class="cvfs-meta"><?php echo esc_html( $cvfs_post['date'] ); ?></div>
							<h2 class="cvfs-bt"><a class="cvfs-stretch" href="<?php echo esc_url( $cvfs_post['url'] ); ?>"><?php echo esc_html( $cvfs_post['title'] ); ?></a></h2>
							<?php if ( $cvfs_post['excerpt'] ) : ?>
								<p class="cvfs-ex"><?php echo esc_html( $cvfs_post['excerpt'] ); ?></p>
							<?php endif; ?>
						</div>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
	<?php endif; ?>

	<?php /* 8. Karta över resorna (pluginens [resekarta]) */ ?>
	<?php if ( $map ) : ?>
		<div class="cvfs-band cvfs-mapband">
			<div class="cvfs-wrap cvfs-map">
				<?php echo $map; // phpcs:ignore WordPress.Security.EscapeOutput -- HTML från [resekarta]. ?>
			</div>
		</div>
	<?php endif; ?>

	<?php /* 9. Band "Följ oss" */ ?>
	<div class="cvfs-wrap">
		<div class="cvfs-social">
			<div class="cvfs-social-txt">
				<h2><?php echo esc_html( $s['band']['title'] ); ?></h2>
				<p><?php echo esc_html( $s['band']['text'] ); ?></p>
				<div class="cvfs-soc-btns">
					<?php
					foreach ( $s['band']['buttons'] as $cvfs_b ) :
						if ( '' === $cvfs_b['text'] || '' === $cvfs_b['url'] ) {
							continue;
						}
						?>
						<div><a class="cvfs-btn" href="<?php echo esc_url( $cvfs_b['url'] ); ?>"<?php echo $cvfs_b['new_tab'] ? ' target="_blank" rel="noopener"' : ''; ?>><?php echo esc_html( $cvfs_b['text'] ); ?></a></div>
					<?php endforeach; ?>
				</div>
			</div>
			<div class="cvfs-ig">
				<?php
				foreach ( $s['band']['images'] as $cvfs_i ) :
					if ( '' === $cvfs_i['url'] ) {
						continue;
					}
					?>
					<div class="cvfs-igi"><img src="<?php echo esc_url( $cvfs_i['url'] ); ?>" alt="<?php echo esc_attr( $cvfs_i['alt'] ); ?>" loading="lazy" decoding="async" /></div>
				<?php endforeach; ?>
			</div>
		</div>
	</div>


</div>
