<?php
/**
 * Title: Contact Info
 * Slug: egnitech-one/contact-info
 * Categories: egnitech-one
 * Description: Dynamic contact information pulled from theme options.
 */

$title = egnitech_one_get_option( 'egnitech_one_contact_title', __( 'Let\'s build something extraordinary.', 'egnitech-one' ) );
$desc  = egnitech_one_get_option( 'egnitech_one_contact_desc', __( 'Have a project in mind or just want to say hello? Drop us a message and we\'ll get back to you within 24 hours.', 'egnitech-one' ) );
$email = egnitech_one_get_option( 'egnitech_one_contact_email', 'hello@egnitech.com' );
$phone = egnitech_one_get_option( 'egnitech_one_contact_phone', '+1 (555) 000-0000' );
?>
<!-- wp:group {"className":"egnitech-contact-info"} -->
<div class="wp-block-group egnitech-contact-info">
    <!-- wp:heading {"level":1,"style":{"typography":{"lineHeight":"1.1","fontWeight":"700"}}} -->
    <h1 style="font-weight:700;line-height:1.1"><?php echo wp_kses_post( $title ); ?></h1>
    <!-- /wp:heading -->

    <!-- wp:paragraph {"style":{"typography":{"fontSize":"var:preset|font-size|large"}}} -->
    <p class="has-large-font-size"><?php echo wp_kses_post( $desc ); ?></p>
    <!-- /wp:paragraph -->

    <!-- wp:group {"className":"egnitech-contact-details"} -->
    <div class="wp-block-group egnitech-contact-details">
        <?php if ( ! empty( $email ) ) : ?>
            <!-- wp:paragraph {"fontSize":"small","textColor":"secondary"} -->
            <p class="has-secondary-color has-text-color has-small-font-size"><?php echo esc_html( $email ); ?></p>
            <!-- /wp:paragraph -->
        <?php endif; ?>

        <?php if ( ! empty( $phone ) ) : ?>
            <!-- wp:paragraph {"fontSize":"small","textColor":"secondary"} -->
            <p class="has-secondary-color has-text-color has-small-font-size"><?php echo esc_html( $phone ); ?></p>
            <!-- /wp:paragraph -->
        <?php endif; ?>
    </div>
    <!-- /wp:group -->
</div>
<!-- /wp:group -->
