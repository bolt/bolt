<?php

namespace Bolt\Tests\Mocks;

/**
 * Mock Builder for Doctrine objects
 */
class LoripsumMock extends \PHPUnit_Framework_TestCase
{
    public function get($request)
    {
        switch ($request) {
            case '/1/veryshort':
                return '<p>Ecce aliud simile dissimile. </p>';

            case '/medium/decorate/link/1':
                return "<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. <b>Non semper, inquam;</b> <a href='http://loripsum.net/' target='_blank'>Quae duo sunt, unum facit.</a> <b>Ecce aliud simile dissimile.</b> Duo Reges: constructio interrete. Nam et complectitur verbis, quod vult, et dicit plane, quod intellegam; </p>";

            case '/medium/decorate/link/ol/ul/3':
                return "<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. In quibus doctissimi illi veteres inesse quiddam caeleste et divinum putaverunt. At ille pellit, qui permulcet sensum voluptate. <i>Quare conare, quaeso.</i> Quid ei reliquisti, nisi te, quoquo modo loqueretur, intellegere, quid diceret? Duo Reges: constructio interrete. <mark>Itaque contra est, ac dicitis;</mark> </p>

<ol>
    <li>Praeclare, inquit, facis, cum et eorum memoriam tenes, quorum uterque tibi testamento liberos suos commendavit, et puerum diligis.</li>
    <li>Qui autem esse poteris, nisi te amor ipse ceperit?</li>
</ol>

<ul>
    <li>Hanc quoque iucunditatem, si vis, transfer in animum;</li>
    <li>Naturales divitias dixit parabiles esse, quod parvo esset natura contenta.</li>
</ul>

<p>Videmus igitur ut conquiescere ne infantes quidem possint. Quis non odit sordidos, vanos, leves, futtiles? Tum Quintus: Est plane, Piso, ut dicis, inquit. <i>Nihil sane.</i> Theophrasti igitur, inquit, tibi liber ille placet de beata vita? Iubet igitur nos Pythius Apollo noscere nosmet ipsos. </p>

<p>Quo plebiscito decreta a senatu est consuli quaestio Cn. <a href='http://loripsum.net/' target='_blank'>Non risu potius quam oratione eiciendum?</a> <a href='http://loripsum.net/' target='_blank'>Satis est ad hoc responsum.</a> Sed nonne merninisti licere mihi ista probare, quae sunt a te dicta? Torquatus, is qui consul cum Cn. Quae cum dixisset paulumque institisset, Quid est? Istic sum, inquit. Videmusne ut pueri ne verberibus quidem a contemplandis rebus perquirendisque deterreantur? </p>

";

            default:
                return '<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Quo plebiscito decreta a senatu est consuli quaestio Cn. Illud non continuo, ut aeque incontentae. Duo Reges: constructio interrete. Sed haec in pueris; Sed quid sentiat, non videtis. Cum autem in quo sapienter dicimus, id a primo rectissime dicitur. </p>

<p>Sed haec quidem liberius ab eo dicuntur et saepius. Hunc vos beatum; Iubet igitur nos Pythius Apollo noscere nosmet ipsos. Quis Aristidem non mortuum diligit? Conclusum est enim contra Cyrenaicos satis acute, nihil ad Epicurum. Satis est tibi in te, satis in legibus, satis in mediocribus amicitiis praesidii. An vero, inquit, quisquam potest probare, quod perceptfum, quod. Primum cur ista res digna odio est, nisi quod est turpis? Huius, Lyco, oratione locuples, rebus ipsis ielunior. Quae cum dixisset paulumque institisset, Quid est? Teneo, inquit, finem illi videri nihil dolere. Erat enim res aperta. </p>

<p>Stulti autem malorum memoria torquentur, sapientes bona praeterita grata recordatione renovata delectant. Teneo, inquit, finem illi videri nihil dolere. </p>

<p>Traditur, inquit, ab Epicuro ratio neglegendi doloris. Sin eam, quam Hieronymus, ne fecisset idem, ut voluptatem illam Aristippi in prima commendatione poneret. Sed haec quidem liberius ab eo dicuntur et saepius. Vitiosum est enim in dividendo partem in genere numerare. Quamquam id quidem, infinitum est in hac urbe; </p>

';
        }
    }
}
