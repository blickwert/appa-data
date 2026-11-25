<?php
/**
 * Yoast SEO settings and event SEO.
 *
 * @package BW\Appa
 */

declare(strict_types=1);

namespace BW\Appa\Modules\Seo\Controller;

use BW\Appa\Modules\Events\Repository\EventRepository;
use BW\Appa\Modules\Events\Service\EventFormatter;
use BW\Appa\Modules\Seo\Service\EventSchema;

/**
 * Keeps the site-wide SEO settings in code (the same on dev and live): organisation data, title separator and
 * templates, no index for archives nobody needs; titles and descriptions for the event list and events without own
 * texts; Event schema on event pages; no product pages in the sitemap (products are booked on the event and
 * membership pages).
 */
final class SeoController {

	public const ORGANIZATION = 'APPA – Austrian Positive Psychology Association';
	public const SHORT_NAME   = 'APPA';

	/**
	 * Builds the controller.
	 */
	public function __construct(
		private readonly EventRepository $events,
		private readonly EventFormatter $formatter,
		private readonly EventSchema $schema,
	) {
	}

	/**
	 * Registers hooks (all of them only take effect with Yoast SEO).
	 */
	public function register(): void {
		add_filter( 'option_wpseo_titles', array( $this, 'titles' ) );
		add_filter( 'wpseo_schema_organization', array( $this, 'organization' ) );
		add_filter( 'wpseo_title', array( $this, 'archive_title' ) );
		add_filter( 'wpseo_opengraph_title', array( $this, 'archive_title' ) );
		add_filter( 'wpseo_metadesc', array( $this, 'description' ) );
		add_filter( 'wpseo_opengraph_desc', array( $this, 'description' ) );
		add_filter( 'wpseo_sitemap_exclude_post_type', array( $this, 'exclude_post_type' ), 10, 2 );
		add_filter( 'wpseo_sitemap_exclude_taxonomy', array( $this, 'exclude_taxonomy' ), 10, 2 );
		add_action( 'wp_head', array( $this, 'event_schema' ), 30 );
		add_action( 'wp_head', array( $this, 'organization_schema' ), 30 );
	}

	/**
	 * Site-wide Yoast settings ("Darstellung in der Suche").
	 *
	 * @param mixed $options Stored options.
	 * @return mixed
	 */
	public function titles( mixed $options ): mixed {
		if ( ! is_array( $options ) ) {
			return $options;
		}
		/**
		 * Filters the SEO settings kept in code (keys of the Yoast option `wpseo_titles`).
		 *
		 * @param array<string, mixed> $settings Settings.
		 */
		$settings = (array) apply_filters(
			'bw_appa_seo_settings',
			array(
				'separator'                    => 'sc-ndash',
				'website_name'                 => self::SHORT_NAME,
				'alternate_website_name'       => self::ORGANIZATION,
				'company_or_person'            => 'company',
				'company_name'                 => self::ORGANIZATION,
				'company_alternate_name'       => self::SHORT_NAME,
				'title-page'                   => '%%title%% %%sep%% ' . self::SHORT_NAME,
				'title-post'                   => '%%title%% %%sep%% ' . self::SHORT_NAME,
				'title-' . EventRepository::POST_TYPE => '%%title%% %%sep%% ' . self::SHORT_NAME,
				'title-ptarchive-' . EventRepository::POST_TYPE => __( 'Veranstaltungen zur Positiven Psychologie', 'bw-appa' ) . ' %%sep%% ' . self::SHORT_NAME,
				'metadesc-ptarchive-' . EventRepository::POST_TYPE => self::archive_description(),
				'title-search-wpseo'           => __( 'Suche:', 'bw-appa' ) . ' %%searchphrase%% %%sep%% ' . self::SHORT_NAME,
				'title-404-wpseo'              => __( 'Seite nicht gefunden', 'bw-appa' ) . ' %%sep%% ' . self::SHORT_NAME,
				'disable-author'               => true,
				'disable-date'                 => true,
				'noindex-tax-category'         => true,
				'noindex-tax-post_tag'         => true,
				'noindex-product'              => true,
				'noindex-tax-product_cat'      => true,
				'noindex-tax-product_tag'      => true,
				'noindex-ptarchive-product'    => true,
			)
		);
		return array_merge( $options, $settings );
	}

	/**
	 * Organisation in the schema graph: address, e-mail, logo.
	 *
	 * @param mixed $data Organisation piece.
	 * @return mixed
	 */
	public function organization( mixed $data ): mixed {
		if ( ! is_array( $data ) ) {
			return $data;
		}
		/**
		 * Filters the logo address used in the organisation schema.
		 *
		 * @param string $url Default: logo of the theme "appa".
		 */
		$logo = (string) apply_filters( 'bw_appa_logo_url', get_theme_file_uri( 'assets/images/appa-logo.webp' ) );
		$data['@type']         = array( 'Organization', 'NGO' );
		$data['name']          = self::ORGANIZATION;
		$data['alternateName'] = self::SHORT_NAME;
		$data['email']         = 'info@appa.net';
		$data['areaServed']    = 'AT';
		$data['knowsAbout']    = __( 'Positive Psychologie', 'bw-appa' );
		$data['address']       = array(
			'@type'           => 'PostalAddress',
			'streetAddress'   => 'Plüddemanngasse 104',
			'postalCode'      => '8042',
			'addressLocality' => 'Graz',
			'addressCountry'  => 'AT',
		);
		if ( empty( $data['logo'] ) && '' !== $logo ) {
			$data['logo']  = array( '@type' => 'ImageObject', 'url' => $logo, 'contentUrl' => $logo );
			$data['image'] = $data['logo'];
		}
		return $data;
	}

	/**
	 * Description of the event list.
	 */
	private static function archive_description(): string {
		return __( 'Online-Impulse, Fachgruppen-Abende und die erste APPA-Fachtagung: Entdecke kommende Termine zur Positiven Psychologie und melde dich an.', 'bw-appa' );
	}

	/**
	 * Title of the event list.
	 *
	 * @param mixed $title Title Yoast built.
	 * @return mixed
	 */
	public function archive_title( mixed $title ): mixed {
		return is_post_type_archive( EventRepository::POST_TYPE ) ? __( 'Veranstaltungen zur Positiven Psychologie', 'bw-appa' ) . ' – ' . self::SHORT_NAME : $title;
	}

	/**
	 * The organisation on the front page (Yoast adds its own organisation only with a logo from the media library).
	 */
	public function organization_schema(): void {
		if ( ! is_front_page() || ! defined( 'WPSEO_VERSION' ) ) {
			return;
		}
		$data = $this->organization(
			array(
				'@context' => 'https://schema.org',
				'@id'      => home_url( '/#appa' ),
				'url'      => home_url( '/' ),
			)
		);
		echo '<script type="application/ld+json">' . wp_json_encode( $data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . "</script>\n";
	}

	/**
	 * Events without own description: excerpt plus date; the event list its own description.
	 *
	 * @param mixed $description Description Yoast found.
	 * @return mixed
	 */
	public function description( mixed $description ): mixed {
		if ( '' !== trim( (string) $description ) ) {
			return $description;
		}
		if ( is_post_type_archive( EventRepository::POST_TYPE ) ) {
			return self::archive_description();
		}
		if ( ! is_singular( EventRepository::POST_TYPE ) ) {
			return $description;
		}
		$post_id = (int) get_queried_object_id();
		$when    = $this->formatter->when( $this->events->find( $post_id ) );
		$excerpt = trim( wp_strip_all_tags( (string) get_the_excerpt( $post_id ) ) );
		return trim( ( '' !== $when ? $when . ': ' : '' ) . $excerpt );
	}

	/**
	 * Products only redirect, they are not listed in the sitemap.
	 *
	 * @param mixed  $exclude   Whether to exclude.
	 * @param string $post_type Post type.
	 */
	public function exclude_post_type( mixed $exclude, string $post_type = '' ): bool {
		return 'product' === $post_type ? true : (bool) $exclude;
	}

	/**
	 * Same for the product and post taxonomies.
	 *
	 * @param mixed  $exclude  Whether to exclude.
	 * @param string $taxonomy Taxonomy.
	 */
	public function exclude_taxonomy( mixed $exclude, string $taxonomy = '' ): bool {
		return in_array( $taxonomy, array( 'product_cat', 'product_tag', 'category', 'post_tag' ), true ) ? true : (bool) $exclude;
	}

	/**
	 * schema.org Event on event pages.
	 */
	public function event_schema(): void {
		if ( ! is_singular( EventRepository::POST_TYPE ) ) {
			return;
		}
		$post_id = (int) get_queried_object_id();
		$event   = $this->events->find( $post_id );
		if ( ! $event->has_date() ) {
			return;
		}
		$schema = $this->schema->build(
			$event,
			array(
				'title'         => get_the_title( $post_id ),
				'url'           => (string) get_permalink( $post_id ),
				'description'   => trim( wp_strip_all_tags( (string) get_the_excerpt( $post_id ) ) ),
				'image'         => (string) get_the_post_thumbnail_url( $post_id, 'large' ),
				'price_member'  => (string) get_post_meta( $post_id, EventRepository::META_PRICE_MEMBER, true ),
				'price_public'  => (string) get_post_meta( $post_id, EventRepository::META_PRICE_PUBLIC, true ),
				'organizer'     => self::ORGANIZATION,
				'organizer_url' => home_url( '/' ),
			)
		);
		echo '<script type="application/ld+json">' . wp_json_encode( $schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . "</script>\n";
	}
}
