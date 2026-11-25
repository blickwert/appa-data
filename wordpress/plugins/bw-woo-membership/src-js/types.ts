/**
 * Data of the admin app: the page configuration from PHP and the answers of the REST routes bw-wm/v1.
 *
 * Source of truth: AdminPageController::enqueue() (config), AdminRestController and
 * MemberAdminService (list, get, update, create, pending_proofs). Dates are already formatted
 * by PHP ("1. Jänner 2027"), except the ISO fields named below.
 */

/** window.bwWmAdmin, set inline by AdminPageController::enqueue(). */
export interface AdminConfig {
	/** admin.php?page=bw-wm-members */
	page: string;
	/** admin-post.php?action=bw_wm_export&_wpnonce=… (CSV of all members). */
	exportUrl: string;
	/** admin-post.php?action=bw_wm_journal&_wpnonce=… (fee journal, takes from/to). */
	journalUrl: string;
	/** admin-post.php?action=bw_wm_proof_file&_wpnonce=… (takes user). */
	proofUrl: string;
	/** Member documents (post type bw_member_doc). */
	docsUrl: string;
}

declare global {
	interface Window {
		bwWmAdmin?: Partial< AdminConfig >;
	}
}

/** Membership states (Model\Membership::STATUS_*). */
export type MemberStatus = 'active' | 'pending' | 'on-hold' | 'expired' | 'none';

/** Where the membership comes from (Membership::$source). */
export type MemberSource = 'order' | 'subscription' | 'manual' | 'none';

/** Proof states (ProofRepository). */
export type ProofStatus = 'none' | 'pending' | 'approved' | 'rejected';

/** One list row: MemberAdminService::row(). */
export interface MemberRow {
	id: number;
	/** Membership number, '' while none is assigned. */
	number: string;
	name: string;
	email: string;
	/** Visible name of the membership type, '' without membership. */
	type: string;
	/** Key of the membership type (regular, student, honorary, institutional) or ''. */
	type_key: string;
	status: MemberStatus;
	/** Visible name of the status. */
	status_l: string;
	since: string;
	renewal: string;
	/** Visible proof state ('–' when not relevant). */
	proof: string;
	directory: boolean;
	source: MemberSource;
}

/** Counts per status plus open proofs: MemberAdminService::counts(). */
export interface MemberCounts {
	all: number;
	active: number;
	pending: number;
	'on-hold': number;
	expired: number;
	none: number;
	proofs: number;
}

/** GET /bw-wm/v1/members */
export interface MemberList {
	items: MemberRow[];
	total: number;
	counts: MemberCounts;
}

/** Keys of the membership types (MembershipTypes::all()). */
export type MemberTypeKey = 'regular' | 'student' | 'honorary' | 'institutional';

/** Query of GET /bw-wm/v1/members ('' = no filter). A type alias, so addQueryArgs() takes it. */
export type MemberListQuery = {
	status: '' | Exclude< MemberStatus, 'none' >;
	type: '' | MemberTypeKey;
	search: string;
	page: number;
	per_page: number;
};

/** Public directory profile: MemberRepository::profile(). */
export interface MemberProfile {
	title: string;
	website: string;
	focus: string;
	bio: string;
	/** Keys of ProfileFields::groups(). */
	groups: string[];
}

/** Manual membership (honorary, institutional). */
export interface ManualMembership {
	/** Key of the membership type or '' (from order/subscription). */
	type: string;
	/** ISO date (Y-m-d) or '' (unlimited). */
	until: string;
}

/** Proof block of the member details. */
export interface MemberProof {
	status: ProofStatus;
	label: string;
	uploaded: string;
	valid_until: string;
	reviewed: string;
	reason: string;
	has_file: boolean;
	/** ISO date (Y-m-d) suggested as end of validity. */
	suggested: string;
}

/** One order of the member. */
export interface MemberOrder {
	id: number;
	/** d.m.Y */
	date: string;
	status: string;
	total: string;
	items: string;
	url: string;
}

/** GET/POST /bw-wm/v1/members/{id}, POST /bw-wm/v1/members: MemberAdminService::get(). */
export interface MemberDetail {
	row: MemberRow;
	first: string;
	last: string;
	email: string;
	manual: ManualMembership;
	profile: MemberProfile;
	application: {
		occupation: string;
		/** ISO date (Y-m-d) of the statutes confirmation or ''. */
		statutes: string;
	};
	valid: string;
	proof: MemberProof;
	orders: MemberOrder[];
	/** Membership types: key => visible name. */
	types: Record< string, string >;
	/** Specialist groups: key => visible name. */
	groups: Record< string, string >;
}

/** Body of POST /bw-wm/v1/members/{id} (all fields optional on the server). */
export interface MemberUpdate {
	first: string;
	last: string;
	email: string;
	manual: ManualMembership;
	profile: MemberProfile;
	directory: boolean;
}

/** Body of POST /bw-wm/v1/members (new manual member). */
export interface MemberCreate {
	first: string;
	last: string;
	email: string;
	type: Extract< MemberTypeKey, 'honorary' | 'institutional' >;
	/** ISO date (Y-m-d) or '' (unlimited). */
	until: string;
}

/** One entry of GET /bw-wm/v1/proofs: a list row whose proof field holds the pending file. */
export interface PendingProof extends Omit< MemberRow, 'proof' > {
	proof: {
		uploaded: string;
		/** Original file name. */
		name: string;
		/** ISO date (Y-m-d) suggested as end of validity. */
		suggested: string;
	};
}

/** Body of POST /bw-wm/v1/proofs/{id}. */
export interface ProofDecision {
	decision: 'approve' | 'reject';
	/** ISO date (Y-m-d). */
	valid_until: string;
	reason: string;
}

/** Answer of POST /bw-wm/v1/proofs/{id}. */
export interface ProofDecisionResult {
	ok: true;
}
