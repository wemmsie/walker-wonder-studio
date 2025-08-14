<head>
	<meta charset='<?php bloginfo('charset'); ?>'>
	<meta name='viewport' content='width=device-width, initial-scale=1.0'>
	<link rel='profile' href='http://gmpg.org/xfn/11'>
	<link rel='pingback' href='<?php bloginfo('pingback_url'); ?>'>
	<?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>

	<header class='nav-parent'>
		<div class='nav-container gutter'>
			<nav id='site-navigation' class='main-nav'>
				<div class='logo'>
					<a href='/'>
						<?php require_once get_template_directory() . '/assets/svg/dap-logo.svg'; ?>
					</a>
				</div>
				<div class='werk'>
					<a href='/about/'>
						<button class='pill pill-sm pill-blue'>Work with us</button>
					</a>
					<div class='spacer'></div>
				</div>
				<button class="menu-toggle" aria-controls="primary-menu" aria-expanded="false">
					<span class="burger"></span>
					<span class="burger"></span>
					<span class="burger"></span>
				</button>
				<div class='drawer'>
					<div class='sub-drawer'>
						<div class='site-info'>
							<p class='site-description'><?php bloginfo('description'); ?></p>
							<div class='socials'>
								<?php if (have_rows('socials', 'options')) : ?>
									<?php while (have_rows('socials', 'options')) :
										the_row(); ?>
										<?php
										$link = get_sub_field('link', 'options');
										$icon = get_sub_field('icon', 'options');
										if ($icon) :
											$link_url = $link['url'];
										?>
											<a href='<?php echo esc_url($link_url); ?>' class='social'>
												<img src="<?php echo esc_url($icon['url']); ?>" alt="<?php echo esc_attr($icon['alt']); ?>" class='object-contain' />
											</a>
										<?php endif; ?>
									<?php endwhile; ?>
								<?php endif; ?>
							</div>
						</div>
						<?php
						wp_nav_menu(array(
							'theme_location' => 'primary',
							'menu_id' => 'primary-menu',
							'walker' => new Zorvek_Walker(),
						));
						?>
					</div>
					<div class='divider'></div>
					<div class='sub-drawer'>
						<div class='search-container'>
							<form role="search" method="get" id="searchform" action="<?php echo esc_url(home_url('/')); ?>">
								<input type="text" name="s" id="s" placeholder="Search" class='search' value="<?php echo get_search_query(); ?>" />
								<button type="submit"><i class="icon fa-solid fa-magnifying-glass"></i></button>
							</form>

						</div>

					</div>
				</div>
		</div>

		</nav><!-- #site-navigation -->
		</div>
	</header><!-- #header -->

	<div id='content' class='site-content'>