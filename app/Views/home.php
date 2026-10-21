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
            <div class="benefit-grid">
                <?php foreach ($products as $product): ?>
                    <article>
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
            <div class="benefit-grid">
                <article>
                    <h3>Plan and prepare</h3>
                    <p>Consultation, system design, and permitting support.</p>
                </article>
                <article>
                    <h3>Install</h3>
                    <p>Professional system installation for your property.</p>
                </article>
                <article>
                    <h3>Maintain</h3>
                    <p>Maintenance, repairs, and system monitoring.</p>
                </article>
            </div>
            <a class="landing-cta" href="<?= site_url('register') ?>">Schedule a consultation</a>
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
        <p>Call <a href="tel:09291230983">0929 123 0983</a> or <a href="mailto:katherine.sinagaraw@sunsonsolar.com">email our team</a>.</p>
        <small>© Sun Son Solar. All rights reserved. · <a href="#about">Privacy and terms</a></small>
    </footer>
</body>
</html>
