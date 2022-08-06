<?php
/**
 * WordPress Settings Framework
 *
 * @author  Gilbert Pellegrom, James Kemp
 * @link    https://github.com/gilbitron/WordPress-Settings-Framework
 * @license MIT
 */

/**
 * Define your settings
 *
 * The first parameter of this filter should be wpsf_register_settings_[options_group],
 * in this case "my_example_settings".
 *
 * Your "options_group" is the second param you use when running new WordPressSettingsFramework()
 * from your init function. It's important as it differentiates your options from others.
 *
 * To use the tabbed example, simply change the second param in the filter below to 'wpsf_tabbed_settings'
 * and check out the tabbed settings function on line 156.
 */

add_filter( 'wpsf_register_settings_waav', 'wpsf_tabbed_settings' );

/**
 * Tabless example.
 *
 * @param array $wpsf_settings Settings.
 */
function wpsf_tabless_settings( $wpsf_settings ) {
	// General Settings section
	$wpsf_settings[] = array(
		'section_id'          => 'general',
		'section_title'       => 'General Configuration',
		'section_description' => 'Connect to ShipStation API & start validating addresses.',
		'section_order'       => 5,
		'fields'              => array(
            array(
				'id'      => 'waav_enable',
				'title'   => 'Enable',
				'desc'    => 'Enable WooCommerce address validation.',
				'type'    => 'checkbox',
				'default' => 0,
			),
			array(
				'id'          => 'waav_api',
				'title'       => 'API Key',
				'desc'        => 'Please enter your ShipStation API key in this field.',
				'placeholder' => '',
				'type'        => 'text',
			),
        ),
	);

    /* 
	// More Settings section.
	$wpsf_settings[] = array(
		'section_id'    => 'more',
		'section_title' => 'More Settings',
		'section_order' => 10,
		'fields'        => array(
			array(
				'id'      => 'section-control',
				'title'   => 'Will show Additional Settings Group if toggled',
				'flux-checkout',
				'type'    => 'toggle',
				'default' => false,
			),
		),
	);

	$wpsf_settings[] = array(
		'section_id'            => 'additional',
		'section_title'         => 'Additional Settings',
		'section_order'         => 10,
		'section_control_group' => 'section-control',
		'show_if'               => array( // Field will only show, if the control `more_section-control` is set to true.
			array(
				'field' => 'more_section-control',
				'value' => array( '1' ),
			),
		),
		'fields'                => array(
			array(
				'id'      => 'additional-text',
				'title'   => 'Additional Text',
				'desc'    => 'This is a description.',
				'type'    => 'text',
				'default' => 'This is default',
			),
			array(
				'id'      => 'additional-number',
				'title'   => 'Additional Number',
				'desc'    => 'This is a description.',
				'type'    => 'number',
				'default' => 10,
			),
		),
	); 
    */

	return $wpsf_settings;
}

/**
 * Tabbed example.
 *
 * @param array $wpsf_settings settings.
 */
function wpsf_tabbed_settings( $wpsf_settings ) {
	// Tabs.
	$wpsf_settings['tabs'] = array(
		array(
			'id'    => 'tab_1',
			'title' => esc_html__( 'General', 'text-domain' ),
		),
		array(
			'id'    => 'tab_2',
			'title' => esc_html__( 'Email', 'text-domain' ),
            /* 'tab_control_group' => 'tab-control',
			'show_if'           => array( // Field will only show if the control `tab_2_section_2_tab-control` is set to true.
				array(
					'field' => 'tab_1_additional_send-reminder-emails',
					'value' => array( '1' ),
				),
			), */
		),
	);

	// Settings.
	$wpsf_settings['sections'] = array(
		array(
			'tab_id'        => 'tab_1',
			'section_id'          => 'general',
            'section_title'       => 'General Configuration',
            'section_description' => 'Connect to ShipEngine API & start validating addresses.',
			'section_order' => 10,
			'fields'        => array(
				array(
                    'id'      => 'enable',
                    'title'   => 'Enable',
                    'desc'    => 'Enable WooCommerce address validation.',
                    'type'    => 'checkbox',
                    'default' => 0,
                ),
                array(
                    'id'          => 'api-shipengine',
                    'title'       => 'API Key',
                    'desc'        => 'Please enter your ShipEngine API key in this field.',
                    'placeholder' => '',
                    'type'        => 'text',
                ),
			),
		),
		array(
			'tab_id'        => 'tab_1',
			'section_id'    => 'additional',
            'section_title'       => 'Additional Options',
			'section_order' => 10,
			'fields'        => array(
				array(
                    'id'      => 'disable-auto-correct',
                    'title'   => 'Disable auto correct',
                    'desc'    => 'Disable address auto correct when order/subscription is created in WooCommerce.',
                    'type'    => 'checkbox',
                    'default' => 0,
                ),
				array(
                    'id'      => 'disable-validation-orders',
                    'title'   => 'Disable order validation',
                    'desc'    => 'Disable address validation only for incoming orders.',
                    'type'    => 'checkbox',
                    'default' => 0,
                ),
				array(
                    'id'      => 'disable-validation-subscriptions',
                    'title'   => 'Disable subscription validation',
                    'desc'    => 'Disable address validation only for incoming subscripitons.',
                    'type'    => 'checkbox',
                    'default' => 0,
                ),
            ),
		),
		array(
			'tab_id'        => 'tab_2',
			'section_id'    => 'email_reminder',
			'section_title' => 'Email Configuration',
			'section_order' => 10,
			'fields'        => array(
				array(
                    'id'      => 'send-reminder-emails',
                    'title'   => 'Send Email Reminders',
					'subtitle' => 'Only works with WooCommerce Subscriptions',
                    'type'    => 'toggle',
                    'default' => false,
                ),
				array(
					'id'       => 'reocurring-enable',
					'title'    => 'Reocurring reminders',
					'subtitle' => '',
					'type'     => 'select',
					'choices'  => array(
						0 => 'No',
						1 => 'Yes',
					),
					'default'  => 0,
				),
				array(
					'id'      => 'reocurring-days',
					'title'   => 'Reocurring day(s)',
					'desc'    => 'Send email reminder every X days. Reminders stop once customer confirms / updates shipping address.',
					'type'    => 'number',
					'default' => 7,
					'show_if'  => array( // Field will only show, if the control `email_reminder-reocurring-enable` is set to 1.
						array(
							'field' => 'tab_2_email_reminder_reocurring-enable',
							'value' => array( 1 ),
						),
					),
				),
				array(
					'id'      => 'subject',
					'title'   => 'Subject',
					'desc'    => 'This subject will be used while sending email to the customer.',
					'type'    => 'text',
					'default' => '',
				),
                array(
                    'id'      => 'body',
                    'title'   => 'Body',
                    'type'    => 'editor',
                    'default' => '',
                ),
			),
		),
	);

	return $wpsf_settings;
}