<?php
/**
 * Front page template for the Christian clinic stage site.
 *
 * @package hello-elementor-child
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

get_header();
?>
<section class="hero">
    <div class="container">
        <p class="eyebrow">Compassionate Care</p>
        <h1>Whole-person care for your body, mind, and spirit.</h1>
        <p>Christian clinic services with professional medical care, clear guidance, and practical support for families.</p>
        <div class="hero-actions">
            <a class="btn btn-primary" href="#appointments">Book Appointment</a>
            <a class="btn btn-secondary" href="#services">Explore Services</a>
        </div>
    </div>
</section>

<section id="services" class="section">
    <div class="container grid-3">
        <article class="card">
            <h2>Primary Care</h2>
            <p>Routine checkups, chronic care planning, and preventive medicine for adults and children.</p>
        </article>
        <article class="card">
            <h2>Family Counseling</h2>
            <p>Faith-informed counseling and practical support for marriage, parenting, and personal wellness.</p>
        </article>
        <article class="card">
            <h2>Wellness Programs</h2>
            <p>Nutrition coaching, prayer support, and habit-building plans that fit your daily life.</p>
        </article>
    </div>
</section>

<section id="appointments" class="section section-soft">
    <div class="container">
        <h2>Ready to get started?</h2>
        <p>Set up your consultation and our team will guide your next steps.</p>
        <p><a class="btn btn-primary" href="/contact">Contact the Clinic</a></p>
    </div>
</section>
<?php
get_footer();
