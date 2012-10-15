<?php

$html = "

    <h1>Sed ne, dum huic obsequor, vobis molestus sim.</h1>

<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Cur ipse Pythagoras et Aegyptum lustravit et Persarum magos adiit? Eam tum adesse, cum dolor omnis absit; Quae diligentissime contra Aristonem dicuntur a Chryippo. <a href='http://loripsum.net/' target='_blank'>Scisse enim te quis coarguere possit?</a> </p>

<p>Non dolere, inquam, istud quam vim habeat postea videro; A quibus propter discendi cupiditatem videmus ultimas terras esse peragratas. Nam et complectitur verbis, quod vult, et dicit plane, quod intellegam; Indicant pueri, in quibus ut in speculis natura cernitur. Sin dicit obscurari quaedam nec apparere, quia valde parva sint, nos quoque concedimus; <b>Quis hoc dicit?</b> Maximas vero virtutes iacere omnis necesse est voluptate dominante. <i>Equidem, sed audistine modo de Carneade?</i> Sin dicit obscurari quaedam nec apparere, quia valde parva sint, nos quoque concedimus; </p>

<p><a href='http://loripsum.net/' target='_blank'>Easdemne res?</a> <a href='http://loripsum.net/' target='_blank'>Hoc non est positum in nostra actione.</a> Quam ob rem tandem, inquit, non satisfacit? Eam si varietatem diceres, intellegerem, ut etiam non dicente te intellego; Si enim ad populum me vocas, eum. Ne in odium veniam, si amicum destitero tueri. Te ipsum, dignissimum maioribus tuis, voluptasne induxit, ut adolescentulus eriperes P. <i>Confecta res esset.</i> </p>

<h2>Non quaero, quid dicat, sed quid convenienter possit rationi et sententiae suae dicere.</h2>

<p>Magno hic ingenio, sed res se tamen sic habet, ut nimis imperiosi philosophi sit vetare meminisse. Esse enim quam vellet iniquus iustus poterat inpune. <b>Quorum altera prosunt, nocent altera.</b> Deinde disputat, quod cuiusque generis animantium statui deceat extremum. Paulum, cum regem Persem captum adduceret, eodem flumine invectio? Nihil opus est exemplis hoc facere longius. Sic consequentibus vestris sublatis prima tolluntur. Nam memini etiam quae nolo, oblivisci non possum quae volo. Diodorus, eius auditor, adiungit ad honestatem vacuitatem doloris. </p>

<ol>
	<li>Num igitur utiliorem tibi hunc Triarium putas esse posse, quam si tua sint Puteolis granaria?</li>
	<li>Oratio me istius philosophi non offendit;</li>
	<li>Mihi quidem Homerus huius modi quiddam vidisse videatur in iis, quae de Sirenum cantibus finxerit.</li>
	<li>Quicquid porro animo cernimus, id omne oritur a sensibus;</li>
	<li>Materiam vero rerum et copiam apud hos exilem, apud illos uberrimam reperiemus.</li>
</ol>


<p>Sine ea igitur iucunde negat posse se vivere? Age nunc isti doceant, vel tu potius quis enim ista melius? Hoc mihi cum tuo fratre convenit. Sed erat aequius Triarium aliquid de dissensione nostra iudicare. At Zeno eum non beatum modo, sed etiam divitem dicere ausus est. Nihil illinc huc pervenit. Ita graviter et severe voluptatem secrevit a bono. </p>

<ul>
	<li>Quod cum accidisset ut alter alterum necopinato videremus, surrexit statim.</li>
	<li>In his igitur partibus duabus nihil erat, quod Zeno commutare gestiret.</li>
	<li>De ingenio eius in his disputationibus, non de moribus quaeritur.</li>
	<li>Ergo, si semel tristior effectus est, hilara vita amissa est?</li>
</ul>


<p>Duo enim genera quae erant, fecit tria. <a href='http://loripsum.net/' target='_blank'>Maximus dolor, inquit, brevis est.</a> Nos paucis ad haec additis finem faciamus aliquando; <a href='http://loripsum.net/' target='_blank'>Memini me adesse P.</a> Omnia contraria, quos etiam insanos esse vultis. </p>

<p>Cum audissem Antiochum, Brute, ut solebam, cum M. Eadem nunc mea adversum te oratio est. <i>Si quidem, inquit, tollerem, sed relinquo.</i> At iste non dolendi status non vocatur voluptas. Iubet igitur nos Pythius Apollo noscere nosmet ipsos. Itaque hic ipse iam pridem est reiectus; An potest cupiditas finiri? Utilitatis causa amicitia est quaesita. Minime vero, inquit ille, consentit. Suo enim quisque studio maxime ducitur. Nihilne est in his rebus, quod dignum libero aut indignum esse ducamus? </p>

<p>Duo Reges: constructio interrete. Si alia sentit, inquam, alia loquitur, numquam intellegam quid sentiat; An, partus ancillae sitne in fructu habendus, disseretur inter principes civitatis, P. At quicum ioca seria, ut dicitur, quicum arcana, quicum occulta omnia? </p>

<blockquote cite='http://loripsum.net'>
    Idemne potest esse dies saepius, qui semel fuit?
</blockquote>
";


include 'markdownify_extra.php';
$md = new Markdownify(false, 90, false);

$output = $md->parseString($html);

echo nl2br(htmlentities($output));


