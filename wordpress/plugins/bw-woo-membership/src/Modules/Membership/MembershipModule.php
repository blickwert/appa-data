<?php
/**
 * Membership module.
 *
 * @package BW\WooMembership
 */

declare(strict_types=1);

namespace BW\WooMembership\Modules\Membership;

use BW\WooMembership\Core\AbstractModule;
use BW\WooMembership\Modules\Membership\Controller\AccessController;
use BW\WooMembership\Modules\Membership\Controller\AdminPageController;
use BW\WooMembership\Modules\Membership\Controller\AdminRestController;
use BW\WooMembership\Modules\Membership\Controller\ProofController;
use BW\WooMembership\Modules\Membership\Controller\PurchaseController;
use BW\WooMembership\Modules\Membership\Controller\JoinController;
use BW\WooMembership\Modules\Membership\Controller\JoinProofController;
use BW\WooMembership\Modules\Membership\Controller\BlocksController;
use BW\WooMembership\Modules\Membership\Controller\ConfirmationController;
use BW\WooMembership\Modules\Membership\Controller\OrderController;
use BW\WooMembership\Modules\Membership\Controller\ProductFieldController;
use BW\WooMembership\Modules\Membership\Controller\ProfileController;
use BW\WooMembership\Modules\Membership\Controller\ShopController;
use BW\WooMembership\Modules\Membership\Controller\SubscriptionController;
use BW\WooMembership\Modules\Membership\Controller\SyncController;
use BW\WooMembership\Modules\Membership\Repository\MemberRepository;
use BW\WooMembership\Modules\Membership\Repository\MembershipOrderRepository;
use BW\WooMembership\Modules\Membership\Repository\ProofRepository;
use BW\WooMembership\Support\Mailer;
use BW\WooMembership\Modules\Membership\Service\MemberAdminService;
use BW\WooMembership\Modules\Membership\Service\ProofService;
use BW\WooMembership\Modules\Membership\Service\DashboardModel;
use BW\WooMembership\Modules\Membership\Service\JoinProof;
use BW\WooMembership\Modules\Membership\Service\JournalService;
use BW\WooMembership\Modules\Membership\Service\MemberPages;
use BW\WooMembership\Modules\Membership\Service\MembershipService;
use BW\WooMembership\Modules\Membership\Service\MembershipSync;

/**
 * Membership status from WooCommerce, member numbers, protected member area, profile and confirmation.
 */
final class MembershipModule extends AbstractModule {

	/**
	 * {@inheritDoc}
	 */
	public function id(): string {
		return 'membership';
	}

	/**
	 * {@inheritDoc}
	 */
	public function name(): string {
		return __( 'Mitgliedschaft', 'bw-woo-membership' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function description(): string {
		return __( 'Mitgliedschaft auf Basis von WooCommerce: Status, Nummer, Zugang, Mitgliederbereich und Verwaltung.', 'bw-woo-membership' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function register(): void {
		$members     = new MemberRepository();
		$orders      = new MembershipOrderRepository();
		$memberships = new MembershipService( $members, $orders );
		$proofs      = new ProofRepository();
		$mailer      = new Mailer();
		$model       = new DashboardModel( $memberships, $members, $orders, $proofs );
		$sync        = new MembershipSync( $memberships, $members, $orders );
		$proof       = new ProofService( $proofs, $memberships, $mailer );
		$admin       = new MemberAdminService( $memberships, $members, $orders, $proofs, $sync );

		( new ProductFieldController() )->register();
		( new OrderController( $members, $orders ) )->register();
		( new AccessController( $memberships, new MemberPages() ) )->register();
		( new BlocksController( $model ) )->register();
		( new ProfileController( $members ) )->register();
		( new ConfirmationController( $memberships, $model ) )->register();
		( new SyncController( $sync, $orders ) )->register();
		( new SubscriptionController( $orders, $proofs ) )->register();
		( new ShopController( $orders ) )->register();
		( new ProofController( $proof, $proofs, $memberships, $mailer ) )->register();
		( new PurchaseController( $memberships, $orders, $proofs ) )->register();
		$join_proof = new JoinProof( $proofs, $proof );
		( new JoinController( $memberships, $orders, $members, new MemberPages(), $join_proof ) )->register();
		( new JoinProofController( $join_proof, $orders ) )->register();
		add_action( 'bw_wm_daily_after_sync', array( $proof, 'cleanup_stash' ) );
		( new AdminRestController( $admin, $proof ) )->register();
		( new AdminPageController( $admin, $proof, new JournalService( $orders, $members ) ) )->register();
		add_action( 'bw_wm_setup', array( $sync, 'sync_all' ) );
	}
}
