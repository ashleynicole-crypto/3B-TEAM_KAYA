<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sun Son Solar | Power Your Future</title>
    <link rel="stylesheet" href="<?= base_url('style1.css') ?>">
</head>
<body class="landing-body">
    <main class="landing-page">
        <div class="landing-photo">
            <img src="<?= base_url('PCTURES/indexbgi.jpg') ?>" alt="Sun Son Solar">
        </div>

        <section class="landing-content">
            <nav class="landing-nav">
                <a href="<?= site_url('login') ?>">Log in</a>
                <a class="landing-nav__signup" href="<?= site_url('register') ?>">Create account</a>
            </nav>

            <div class="landing-copy">
                <img class="landing-logo" src="<?= base_url('PCTURES/sunsun%20logo.png') ?>" alt="Sun Son Solar">
                <h1>Power Your Future with Solar</h1>
                <p>Sun Son Solar helps homes and businesses take control of energy with expert solar solutions, based in Pasig City.</p>
                <a class="landing-cta" href="<?= site_url('register') ?>">Request a service</a>
            </div>

            <footer class="landing-footer">
                <a href="#about">About →</a>
                <a href="#products">Products →</a>
                <a href="#services">Services →</a>
                <a href="#stories">Stories →</a>
                <a href="#news">News →</a>
                <a href="#contact">Contact →</a>
            </footer>
        </section>
    </main>

    <section class="home-info" id="about">
        <div class="home-info__inner">
            <p class="home-eyebrow">SUN SON SOLAR · PASIG CITY</p>
            <h2>Make clean energy work for you.</h2>
            <p>Founded by Katherine “Kat” Sinagaraw and her father Santino “Sonny” Sinagaraw, Sun Son Solar helps customers plan, install, and care for solar energy systems.</p>

            <div class="benefit-grid">
                <article>
                    <h3>Save on energy</h3>
                    <p>Generate power on site and reduce reliance on grid electricity.</p>
                </article>
                <article>
                    <h3>Choose sustainability</h3>
                    <p>Use renewable energy and reduce your household or business footprint.</p>
                </article>
                <article>
                    <h3>Build independence</h3>
                    <p>Choose a system that fits your energy use and goals.</p>
                </article>
            </div>
        </div>
    </section>

    <section class="home-info home-info--services" id="products">
        <div class="home-info__inner">
            <p class="home-eyebrow">OUR PRODUCTS</p>
            <h2>Components for solar systems.</h2>
            <div class="benefit-grid product-grid">
                <?php foreach ($products as $product): ?>
                    <article>
                        <img class="product-image" src="<?= esc(product_image_url($product)) ?>" alt="<?= esc($product['product_name']) ?>" loading="lazy">
                        <h3><?= esc($product['product_name']) ?></h3>
                        <p><?= esc($product['description']) ?></p>
                        <?php if ($product['price'] !== null): ?>
                            <p>From ₱<?= number_format((float) $product['price'], 2) ?></p>
                        <?php endif ?>
                    </article>
                <?php endforeach ?>
            </div>
        </div>
    </section>

    <section class="home-info">
        <div class="home-info__inner">
            <p class="home-eyebrow">HOW WE HELP</p>
            <h2 id="services">Solar support from planning to upkeep.</h2>
            <div class="benefit-grid service-grid">
                <?php foreach ($services as $service): ?>
                    <article>
                        <img class="service-image" src="<?= esc(service_image_url($service)) ?>" alt="<?= esc($service['service_name']) ?> service" loading="lazy">
                        <h3><?= esc($service['service_name']) ?></h3>
                        <p><?= esc($service['description'] ?: 'Contact us to discuss this service.') ?></p>
                        <?php if ($service['base_price'] !== null): ?>
                            <p class="service-price">From ₱<?= number_format((float) $service['base_price'], 2) ?></p>
                        <?php endif ?>
                    </article>
                <?php endforeach ?>
            </div>
            <a class="landing-cta" href="<?= site_url('register') ?>">Schedule a consultation</a>
        </div>
    </section>

    <section class="home-info home-info--services editorial-section" id="stories">
        <div class="home-info__inner">
            <p class="home-eyebrow">CUSTOMER STORIES</p>
            <h2>Experiences shared by our customers.</h2>
            <p class="editorial-intro">Customer stories are shared with permission and reviewed by the Sun Son Solar team.</p>
            <?php if ($testimonials): ?>
                <div class="editorial-grid story-grid">
                    <?php foreach ($testimonials as $story): ?>
                        <article class="story-card">
                            <span class="editorial-tag">Customer story</span>
                            <blockquote>“<?= esc($story['quote']) ?>”</blockquote>
                            <p class="story-author"><?= esc($story['first_name'].' '.mb_substr($story['last_name'],0,1).'.') ?></p>
                        </article>
                    <?php endforeach ?>
                </div>
            <?php else: ?>
                <div class="editorial-empty">
                    <p>Customer stories will appear here after customers choose to share them and the team reviews them.</p>
                </div>
            <?php endif ?>
            <a class="editorial-link" href="<?= site_url('login') ?>">Share your Sun Son Solar experience <span aria-hidden="true">→</span></a>
        </div>
    </section>

    <section class="home-info home-info--services editorial-section" id="news">
        <div class="home-info__inner">
            <p class="home-eyebrow">NEWS AND UPDATES</p>
            <h2>From the Sun Son Solar team.</h2>
            <p class="editorial-intro">Project updates, solar tips, and service announcements.</p>
            <?php if ($news): ?>
                <?php $newsTypeLabels = ['promotion' => 'Company news', 'tip' => 'Solar tips', 'update' => 'Service update', 'system' => 'System notice']; ?>
                <div class="editorial-grid news-grid">
                    <?php foreach ($news as $item): ?>
                        <?php
                            $newsType = $item['announcement_type'] ?? 'update';
                            $newsLabel = $newsTypeLabels[$newsType] ?? 'Announcement';
                            $newsDate = date('Y-m-d', strtotime($item['publish_at']));
                            $newsAction = $newsType === 'promotion'
                                ? ['Book an assessment', site_url('register')]
                                : ($newsType === 'tip'
                                    ? ['Explore solar services', '#services']
                                    : ['Contact our team', '#contact']);
                        ?>
                        <article class="news-card">
                            <div class="news-meta">
                                <time datetime="<?= esc($newsDate) ?>"><?= esc(date('M j, Y', strtotime($item['publish_at']))) ?></time>
                                <span class="editorial-tag"><?= esc($newsLabel) ?></span>
                            </div>
                            <h3><?= esc($item['title']) ?></h3>
                            <p><?= nl2br(esc(mb_strimwidth($item['message'], 0, 240, '…'))) ?></p>
                            <a class="editorial-link" href="<?= esc($newsAction[1]) ?>"><?= esc($newsAction[0]) ?> <span aria-hidden="true">→</span></a>
                        </article>
                    <?php endforeach ?>
                </div>
            <?php else: ?>
                <div class="editorial-empty">
                    <p>Company news and service updates will appear here.</p>
                </div>
            <?php endif ?>
        </div>
    </section>

    <section class="home-info home-info--services">
        <div class="home-info__inner">
            <p class="home-eyebrow">COMMON QUESTIONS</p>
            <h2>Before you get started.</h2>

            <details class="faq-item">
                <summary>How do I find out what system fits my property?</summary>
                <p>Request a consultation. The team can discuss your goals, property, and energy use before recommending next steps.</p>
            </details>
            <details class="faq-item">
                <summary>Can I request maintenance or a repair?</summary>
                <p>Yes. Create an account and submit a service request from your customer dashboard.</p>
            </details>
            <details class="faq-item">
                <summary>Where does Sun Son Solar operate?</summary>
                <p>The company’s first office is in Pasig City. Contact the team to ask about service availability for your location.</p>
            </details>
        </div>
    </section>

    <footer class="home-contact" id="contact">
        <p><strong>Sun Son Solar</strong> · Pasig City</p>
        <?php $companyPhone = $siteSettings['company_phone'] ?? '09291230983'; $companyEmail = $siteSettings['company_email'] ?? 'katherine.sinagaraw@sunsonsolar.com'; ?>
        <p>Call <a href="tel:<?= esc($companyPhone) ?>"><?= esc($companyPhone) ?></a> or <a href="mailto:<?= esc($companyEmail) ?>">email our team</a>.</p>
        <p class="social-links">
            <?php foreach (['facebook_url'=>'Facebook','instagram_url'=>'Instagram','linkedin_url'=>'LinkedIn'] as $key=>$label): ?>
                <?php if (!empty($siteSettings[$key])): ?><a href="<?= esc($siteSettings[$key]) ?>" target="_blank" rel="noopener noreferrer"><?= esc($label) ?></a><?php endif ?>
            <?php endforeach ?>
        </p>
        <small>© Sun Son Solar. All rights reserved. · <a href="#privacy">Privacy notice</a></small>
    </footer>
    <section class="home-info" id="privacy">
        <div class="home-info__inner">
            <h2>Privacy notice</h2>
            <p><?= nl2br(esc($siteSettings['privacy_notice'] ?? 'Account and service details are used to manage appointments, provide support, and maintain customer records. Customer stories are shown publicly only after the customer opts in and an administrator approves them.')) ?></p>
        </div>
    </section>
</body>
</html>
