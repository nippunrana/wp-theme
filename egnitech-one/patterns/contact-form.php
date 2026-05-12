<?php
/**
 * Title: Contact Form
 * Slug: egnitech-one/contact-form
 * Categories: egnitech-one
 * Description: Custom HTML contact form for the Contact Us template.
 */
?>
<?php
$fields_raw = egnitech_one_get_option( 'egnitech_one_contact_form_fields', '[]' );
$fields     = json_decode( $fields_raw, true );

// Default fields if none configured
if ( empty( $fields ) ) {
    $fields = array(
        array( 'label' => __( 'Name', 'egnitech-one' ), 'type' => 'text', 'placeholder' => __( 'John Doe', 'egnitech-one' ), 'required' => true ),
        array( 'label' => __( 'Email', 'egnitech-one' ), 'type' => 'email', 'placeholder' => __( 'john@example.com', 'egnitech-one' ), 'required' => true ),
        array( 'label' => __( 'Subject', 'egnitech-one' ), 'type' => 'text', 'placeholder' => __( 'How can we help?', 'egnitech-one' ), 'required' => true ),
        array( 'label' => __( 'Message', 'egnitech-one' ), 'type' => 'textarea', 'placeholder' => __( 'Tell us more about your project...', 'egnitech-one' ), 'required' => true ),
    );
}
?>
<form id="contact-form" class="egnitech-contact-form">
    <?php foreach ( $fields as $field ) : 
        $label       = isset( $field['label'] ) ? $field['label'] : '';
        $type        = isset( $field['type'] ) ? $field['type'] : 'text';
        $placeholder = isset( $field['placeholder'] ) ? $field['placeholder'] : '';
        $required    = ! empty( $field['required'] ) ? 'required' : '';
        $id          = sanitize_title( $label );
        ?>
        <div class="form-group">
            <label for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $label ); ?></label>
            <?php if ( 'textarea' === $type ) : ?>
                <textarea id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $id ); ?>" <?php echo $required; ?> placeholder="<?php echo esc_attr( $placeholder ); ?>"></textarea>
            <?php else : ?>
                <input type="<?php echo esc_attr( $type ); ?>" id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $id ); ?>" <?php echo $required; ?> placeholder="<?php echo esc_attr( $placeholder ); ?>">
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
    <?php 
    $recaptcha_enabled = egnitech_one_get_option( 'egnitech_one_recaptcha_enabled', '' );
    $recaptcha_site_key = egnitech_one_get_option( 'egnitech_one_recaptcha_site_key', '' );
    if ( 'yes' === $recaptcha_enabled && ! empty( $recaptcha_site_key ) ) : ?>
        <div class="form-group recaptcha-group">
            <div class="g-recaptcha" data-sitekey="<?php echo esc_attr( $recaptcha_site_key ); ?>"></div>
        </div>
    <?php endif; ?>

    <div class="form-submit">
        <button type="submit" class="wp-block-button__link">
            <span class="btn-text"><?php esc_html_e( 'Send Message', 'egnitech-one' ); ?></span>
            <span class="btn-icon">→</span>
        </button>
    </div>
</form>
<div id="form-feedback" class="form-feedback"></div>
