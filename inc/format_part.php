<?php
	if (! isset($DEBUG)) { $DEBUG = 0; }

	$aluRevs = '([^[:alnum:]]?(S(ERIES)?)?[[:space:]-]?[0-9]{0,2}([[:space:]:-]?[0-9]{1,2})?[A-Z]?[^[:alnum:]]?)?';
//comparative standard rev format by concatenating universal rev vars below, combined here for reference only
//	$revs =    '([^[:alnum:]]([0-9]{1,2}[:])?[[:alnum:]]{0,3}(S[-]?|RE[VL][-.]?|I[S]{2}?[-]?))';
	$formats = array(
		/* use this for universal formats that can supercede a specific manfid */
		0 => array(
			/* Alcatel-Lucent 300-0609-943 REV. C,600976-713-001,644-0184-003 REV B I, 134-0106-900-ISS-1, 134-0106-900-REV-F*/
			/*'((([0-9]{3}-?[0-9]{4})|([0-9]{6}-?[0-9]{3}))-?[0-9]{3})([^[:alnum:]]?((R(EV)?)|(ISS))?[^[:alnum:]]{0,2}[[:space:]A-Z]{1,3}[0-9]?)?',*/
			'(((((2(44|59|77|89))|(300|400)|(50[09])|(6(22|40|94|95)))-?[0-9]{4})|([0-9]{6}-?[0-9]{3}))-?[0-9]{3})([^[:alnum:]]?((R(EV)?)|(ISS))?[^[:alnum:]]{0,2}[[:space:]A-Z]{1,3}[0-9]?)?',

			/* Alcatel-Lucent ES660C,TN329C,TN1286B*/
			/*'([A-Z]{2}[0-9]{3,4}[A-RT-Z]?)'.$aluRevs,*/
			/*'([A-Z[^((FD)|(FB))]]{2}[0-9]{3,4}[A-RT-Z]?)'.$aluRevs,*/
			/* read more about look-ahead negatives: http://stackoverflow.com/questions/406230/regular-expression-to-match-line-that-doesnt-contain-a-word */
			'(((?!(FB|FD|FC|CS))[A-Z]){2}[0-9]{3,4}[A-RT-Z]?)'.$aluRevs,

			/* Alcatel-Lucent VLNC5,WSRG19B,WSRH1B*/
			'([VW][A-Z]{3}[0-9]{1,2}[A-Z]?)'.$aluRevs,

			/* Alcatel-Lucent 11075L-128*/
			'([0-9]{5}[A-Z][[:space:]-]?[0-9]{3})(([^[:alnum:]]?R(EV)?)?[^[:alnum:]]?[0-9]{1,2})?',

			/* AFC / Tellabs 0470-0009-1B, 0120-0037-RU*/
			'((0101|((0[1-8]|8[16])[0-9]0))-?[01][0-9]{3})(([^[:alnum:]]?R(EV)?)?[^[:alnum:]]{0,2}[0-9][A-Z]?[+]?)?',

			/* Tellabs 825520, 82.5520 */
			'(8[0-4][.]?[245][35][0-9]{2}([ABCDM]{1,2}|ER[0-9])?)(([^[:alnum:]]?R(EV)?)?[^[:alnum:]]?[A-Z])?',

			/* Alcatel-Lucent  ^ED-#[#@]##-### */
			'(ED-?[0-9][[:alnum:]][0-9]{2,3}-?[0-9]{2})([[:alnum:]]*)?',

			/* Alcatel-Lucent  J99351D-11,4,5,A; J99343TM-11 */
			/*(^J[0-9]{5}[A-Za-z]-[0-9])|(^J[0-9]{5}[A-Za-z]{2}-[0-9])*/

			/* Microcodes, MC97780A1, MC97144A1D */
			/* Alcatel-Lucent MC#@###@# - MC1D088A1; MC#@###@#@ - MC1D088A1B; MC#####@# - MC45019A2 */
			'(MC[[:alnum:]]{6}[0-9][A-HJ-Z]?)([^[:alnum:]]?I?[^[:alnum:]]?[0-9][A-Z]?)?',

			/* AG Comm / Alcatel-Lucent / GTD5: FB-27013-A / FB-27013-1A / FB-16271 (changed this to more loose PN-matching 4/20/15 */
			/*'(F[A-Z][-]?0?[0-9]{5}[[:space:]-]?1?[A-C]?[O0-9]?[A-C])([-]?(([0-9]{3})|(I(SS)?[0-9]{1,2})))?',*/
			'(F[A-Z][-]?0?[0-9]{5})([[:space:]-]?1?[A-C]?[O0-9]?[A-C])([-]?(([0-9]{3})|(I(SS)?[0-9]{1,2})))?',

			/* Alcatel-Lucent 410AA,494LA, 364A2 NOT 303RU39A*/
			/*'(4([0-9]{2})[A-Z]{1,2})'.$aluRevs,*/
			'([34]([0-9]{2})((?!(SC|RP|UA|UB|UR|RU))[A-Z]){1,2}[0-9]?)'.$aluRevs,

			/* Alcatel-Lucent 839B5, 739B5*/
			'([1-9][0-9]{2}[A-Z][1-9])'.$aluRevs,

			/* Alcatel-Lucent AKM85,LNW555,AWP6*/
			/*'(AKM[0-9]{1,3}[A-RT-Z]?)'.$aluRevs,*/
			/* Alcatel-Lucent AKM--,BNJ---,AUA---,KFA---,KTU--,LAA---,LEY---,LNW---,AWP--,etc*/
			'((((B[BDNS]|DA|IT|KF|KT|L[ACEJNP]|M[CM]|P[FH]|TO|XM)[A-WY-Z])|(A[CKMNUW][A-RT-WY-Z]|WS[AC]|SP[GMQ]))[0-9]{1,3}[A-RT-Z]?)'.$aluRevs,

			/* Alcatel-Lucent 3AL45028AA,3HE00867CAEAC,3HE03615AA AC01,3TMA2500AA01*/
			'([138](([A-MT]{2}[0-9]{5})|([A-Z]{3}[0-9]{4}))[A-Z]{2})(([^[:alnum:]]?((REV)|(ICS))?[^[:alnum:]]?([A-Z]{0,2}[0-9]{2}|[A-Z]{1,2}[[:space:]]*[0-9]{0,2}))|([A-Z]?[^[:alnum:]]?[[:alnum:]]{2,4}))?',

			/* Alcatel-Lucent / Ascend CBX 11030 */
			'(11[0-9]{3}[A-Z]?(-?128)?)'.$aluRevs,

			/* Austron-Datum 23413016-000-0 23413017-000-0*/
			'(((2[235][0-9]{2})|(1417))[0-9]{4}[-]?[0-9]{3}[-]?[[:alnum:]])((-REV)?[^[:alnum:]]?[A-Z][.]?[0-9]{0,2})?',

			/* Conklin 502-I3-L1, 5553-I4-L1 */
			/*'(5[0-9]{2}-?I[0-9])(-?L[0-9])?',*/
			'((([2359][023567][0-9])|([2-9][0-7,9][0-9]{2}))(RP|SC)?((-?I[0-9])|(-?L[0-9])|(-E[0-9]{3}))*)',

			/* Ericsson ROF-131-708 */
			'(RO[FJ][^[:alnum:]]?[0-9]{3}[^[:alnum:]]?[0-9]{3}[^[:alnum:]]?[[:alnum:]])([^[:alnum:]]?R?[0-9]?[A-Z]?[^[:alnum:]]?[A-Z]?)?',

			/* Siemens Surpass Hit S42024-L5437-A300-17 */
			'(S[0-9]{5}-?[A-Z][0-9]{4}-?[A-Z][0-9]{1,3})(-?[[:alnum:]]?[0-9])?',

			/* Fujitsu FC9565W8C1, FC9511MMF1, FC9580ST41 */
			'(F[A-Z][0-9]{4}(([0-9][A-Z]{2}[0-9])|([A-Z][0-9][A-Z][0-9])|([A-Z]{3}[0-9])|([A-Z]{2}[0-9]{2})|([A-Z][0-9]{3})))([-]?I?[0-9]{1,2}[A-Z]?)?',

			/* GE */
			'(S[MI][0-9]{1,2}[A-Z][0-9]{2}(-?[0-9])?[A-JL-QS-Z]{1,3}[0-9]?(-[[:alnum:]]{1,2})?)',

			/* Link America / GTE-Lenkurt / Lenkurt / Siemens 91420-02 */
			//updated 8-4-16
			/*'(((900|087)-?)?[49][1-3][0-9]{3}-?[0-2][0-9])([A-Z]?[^[:alnum:]]?(I(S{0,2}[^[:alnum:]]?)[0-9]{1,3}[^[:alnum:]]?)?(M(OD)?)?-?[0-9]?)?',*/
			'(((900|087)-)?[259][1-8][0-9]{3}-?[0-2][0-9])([A-Z]?[^[:alnum:]]?(I(S{0,2}[^[:alnum:]]?)[0-9]{1,3}[^[:alnum:]]?)?(M(OD)?)?-?[0-9]?)?',
			'([348][0146789][0-9]{3}-?[0-2][0-9])([A-Z]?[^[:alnum:]]?(I(S{0,2}[^[:alnum:]]?)[0-9]{1,3}[^[:alnum:]]?)?(M(OD)?)?-?[0-9]?)?',

			/* Tollgrade TLGD-DMUPLUS */
			'(TLGD-?[[:alnum:]]{3,7}([^[:alnum:]]?([A-Z]{3,6}|I[0-9][A-Z]?))?(-[ML])?)([^[:alnum:]]?(I|L|ISS)-?[0-9]{1,2})?',

			/* Teltrend DST2496, SDS5486, DNI5760LN */
			'((DST|DNI|SDS)[[:alnum:]]{4}(-?[[:alpha:]]{1,2})?)([^[:alnum:]]?(I(SS)[0-9]))?',


			/* Calix C7 100-00007 */
			'(100-[0-9]{5})([^[:alnum:]]?REV[^[:alnum:]][0-9]{2})?',

			/* Nortel NTLX72AA,NT0H40BC,NTRX51GT,NTR651GT01,NTCA04PQ 04,NT2X90AD-REV. 08,NT0H05ABE5 023,NTHW77AA01, NT4T05AE SERIES:S-01*/
			'(NT[[:alnum:]]{2}[0-9]{2}[A-Z]{2}(E[0-9])?)(([[:space:]]*[^[:alnum:]]?(S(ERIES[:]?(S)?)?|(R((EL)|(EV))[^[:alnum:]]?)|(I[S]{0,2}[0-9]{1,2}))?)?[^[:alnum:]]?[[:alnum:]]{1,3})?',

			/* Bay Networks P109372-32 *
			'(P?-?[0-9]{6}-?(16|32|64))(R?(EV|V)?([0-9]|[A-Za-z]){1,2})?',

			/* Alcatel-Lucent 108003005.006*/
			'([1-46-7]0[0-9]{7})([.\/-][0-9]{3})?',

			/* Alcatel-Lucent Newbridge */
			'(((90)|(87))-[0-9]{4}-[0-9]{2})((-[0-9]{2})?-[A-Z])?',

			/* Adtran 1223426L19-REV A1, 4243924F3, 4243924F1#5, 4243924F1(1164) */
			'([14]2[0-9]{5}[FL][0-9]{1,2})([^[:alnum:]]?(REV[^[:alnum:]]?)?[[:alnum:]]{1,4}[^[:alnum:]]?)?',

			/* Symmetricom, Telecom Solutions */
			'([90][346789][3490]-?[045679][0-9]{4}-?[0-9]{2}[A-Z]{0,3})([^A-Za-z0-9]?REV[^A-Za-z0-9][A-Z]{1,2})?',

			/* Ciena */
			'(1([71][02346]|3[2346]|2[023][02346]|6[026][02346])[^A-Z0-9]([1289][A-Z]{2}[0-9]|[0-35-79][0-9]{3}|[48][1-9][0-9]{2})[^A-Z0-9\/]?[A-Z0-9][0-7][0-9][A-HJ-Z]?)([^[:alnum:]]ISS[0-9])?',
			'(130[^A-Z0-9]([0-35-79][0-9]{3}|[48][1-9][0-9]{2})[^A-Z0-9\/]?[A-Z1-9][0-7][0-9][A-HJ-Z]?)([^[:alnum:]]ISS[0-9])?',
			'((B-?)?(7[0-367]0|820|955)[^A-Z0-9]([1289][A-Z]{2}[0-9]|[0-35-79][0-9]{3}|[48][1-9][0-9]{2})[^A-Z0-9\/]?[A-Z0-9][0-7][0-9][A-HJ-Z]?)([^[:alnum:]]ISS[0-9])?',
			'((B-?)?800[^A-Z0-9][1-2]{2}[0-9]{2}[^A-Z0-9]?[0-9]{3}[A-HJ-Z]?)([^[:alnum:]]ISS[0-9])?',


			/* POST May 14 changes */

			/* General Datacomm 058P150-002 */
			'(0[0-9]{2}[AMP]1[0-9]{2}-0[0-9]{2})([^[:alnum:]]*[A-Z][^[:alnum:]]*[A-Z])?',

			/* Larus 1119-L5 */
			'(1119-?L5)([^[:alnum:]]?ISS[^[:alnum:]]?[0-9]{1,2})?([^[:alnum:]]?(OFFIC[E]?)*[^[:alnum:]]?REP.*)?',

		),
		1 => array(//alcatel-lucent
		),
		19 => array(//nortel
		),
		25 => array(//afc/tellabs
		),
		32 => array(//adc
			'([A-Z]{3}-?[0-9]{3}-?(L[0-9]{1,2})?)([0-9]{2})?',/*HLU-319-L5*/
		),
		46 => array(//austron-datum
		),
		80 => array(//zhone
			'([0-9]{6})',/*717000*/
		),
		83 => array(//ag comm
		),
	);
	$formats[135] = $formats[1];//lucent -> alcatel-lucent
	$formats[48] = $formats[46];//datum -> astron-datum
	$formats[26] = $formats[25];//afc -> tellabs
	$formats[21] = $formats[19];//BAY-NETWORKS -> NORTEL

	// commented rev lines below changed 8-18-16
	//$rev_ext = '([0-9]{1,2}[:])?[[:alnum:]]{0,3}';
	//$rev_ext = '([0-9]{1,2}[:])?[[:alnum:]]{1,3}';
	$rev_ext = '(([0-9]{1,2}[:])?([0-9]{1,2}|[0-9]?[A-Z]|-[A-Z]{1,2}))';
	//$rev_base = '(S(ER)?[-]?|R(E[VL])?[[:space:].-]?|I[S]{2}[[:space:]-]?)';
	$rev_base = '((S(ER)?|R(E[VL])?|I(SS)?)[[:space:].-]?)';
	$rev_kit = '('.$rev_base.$rev_ext.')';
	//$revs = '([^[:alnum:]]+'.$rev_kit.')*';
	$revs = '([[:space:].-]+'.$rev_kit.')*';
	//$abs_revs = '([^[:alnum:]]*(REV|ISS|SER|ICS|REL)[^[:alnum:]]?'.$rev_ext.')*';
	$abs_revs = '([^[:alnum:]]*(REV|ISS|SER|ICS|REL)[^[:alnum:]]?'.$rev_ext.')*';
	function format_part($part,$manfid=0) {
		global $formats;

		$part = preg_replace('/-RF$/','',$part);

		$revs = $GLOBALS['revs'];
		$rev_kit = $GLOBALS['rev_kit'];

//		if ($GLOBALS['DEBUG']) { echo $part.' = '.preg_match('/^'.$rev_kit.'$/',$part).' (manfid '.$manfid.') ::: '; }

		// Force 'Unknown' to search universals
		if ($manfid==146) { $manfid = 0; }

		// base part# without rev
		$form_found = false;//if we found a form match based on $formats per manf
		if (isset($formats[$manfid])) {
			foreach ($formats[$manfid] as $form) {
				if (preg_match('/^'.$form.'/',$part)) {
					$base_part = preg_replace('/^'.$form.$rev_kit.'?$/','$1',$part);
//					echo $part.' = '.$base_part.' = '.$form.'<BR>';
					// if impacting a change, do not try to alter below
					if ($base_part!==$part) {
//						echo $part.' = '.$base_part.' = '.$form.'<BR>';
						$form_found = true;
						break;
					}
				}
			}
		}
		// try universal manf formats now
		if (! $form_found) {
			foreach ($formats[0] as $form) {
				if (preg_match('/^'.$form.'/',$part)) {
					$base_part = preg_replace('/^'.$form.'$/','$1',$part);
//					echo $part.' = '.$base_part.' = '.$form.'<BR>';
					// if impacting a change, do not try to alter below
					if ($base_part!==$part) {
//						echo $part.' = '.$base_part.' = '.$form.'<BR>';
						$form_found = true;
						break;
					}
				}
			}
		}
		// default rev matching for all other cases
		if (! $form_found) {
			$base_part = preg_replace('/'.$revs.'$/','',$part);
			// one last try! using no punct separator between base and rev, but using the more hardlined $abs_revs
			if ($base_part===$part) {
				$base_part = preg_replace('/'.$GLOBALS['abs_revs'].'$/','',$part);
			}
//			if ($GLOBALS['DEBUG']) { echo $part.' = '.$base_part.' === '.$revs.'<BR>'.chr(10); }
		}
//		$base_part = preg_replace('/(S[-]?([0-9]{1,2}[:])?[[:alnum:]]{1,2})*$/','',$part);
//		if ($GLOBALS['DEBUG']) { echo $base_part.'<BR>'.chr(10); }

		return ($base_part);
	}
?>
