<?php

namespace Bolt\Tests\Database\Entity;

use Bolt\Storage\Entity\Content;
use Carbon\Carbon;

/**
 * Content testing entity factory.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class ContentEntityFactory
{
    protected static $_html = <<<EOL
<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Nulla erit controversia. <i>Quae sequuntur igitur?</i></p>
<ol>
<li>Itaque rursus eadem ratione, qua sum paulo ante usus, haerebitis.</li>
<li>In motu et in statu corporis nihil inest, quod animadvertendum esse ipsa natura iudicet?</li>
<li>Sed eum qui audiebant, quoad poterant, defendebant sententiam suam.</li>
<li>Cur post Tarentum ad Archytam?</li>
</ol>
<ul>
<li>Ita multo sanguine profuso in laetitia et in victoria est mortuus.</li>
<li>Quid loquor de nobis, qui ad laudem et ad decus nati, suscepti, instituti sumus?</li>
<li>Nam adhuc, meo fortasse vitio, quid ego quaeram non perspicis.</li>
<li>Deinde disputat, quod cuiusque generis animantium statui deceat extremum.</li>
</ul>
<p>At, si voluptas esset bonum, desideraret. Venit ad extremum; Illum mallem levares, quo optimum atque humanissimum virum, Cn. Sed quid sentiat, non videtis. <i>Facete M.</i> Non ego tecum iam ita iocabor, ut isdem his de rebus, cum L. </p>
<p>Mihi, inquam, qui te id ipsum rogavi? Mihi enim erit isdem istis fortasse iam utendum. Videsne quam sit magna dissensio? Aliud igitur esse censet gaudere, aliud non dolere. Optime, inquam. Duo enim genera quae erant, fecit tria. Quis enim potest ea, quae probabilia videantur ei, non probare? <mark>Refert tamen, quo modo.</mark> Quae cum essent dicta, finem fecimus et ambulandi et disputandi. Quis tibi ergo istud dabit praeter Pyrrhonem, Aristonem eorumve similes, quos tu non probas? <b>Quis enim redargueret?</b> </p>
EOL;

    protected static $_textArea = <<<EOL
<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. <b>Quo modo autem philosophus loquitur?</b> Modo etiam paulum ad dexteram de via declinavi, ut ad Pericli sepulcrum accederem. Si verbum sequimur, primum longius verbum praepositum quam bonum. Hoc dixerit potius Ennius: Nimium boni est, cui nihil est mali. Sed residamus, inquit, si placet. Ecce aliud simile dissimile.</p>
<ol>
<li>Sed in rebus apertissimis nimium longi sumus.</li>
<li>Quid enim necesse est, tamquam meretricem in matronarum coetum, sic voluptatem in virtutum concilium adducere?</li>
<li>Est enim tanti philosophi tamque nobilis audacter sua decreta defendere.</li>
<li>Habes, inquam, Cato, formam eorum, de quibus loquor, philosophorum.</li>
</ol>
<ul>
<li>Primum in nostrane potestate est, quid meminerimus?</li>
<li>-delector enim, quamquam te non possum, ut ais, corrumpere, delector, inquam, et familia vestra et nomine.</li>
<li>Quantum Aristoxeni ingenium consumptum videmus in musicis?</li>
<li>Videmus igitur ut conquiescere ne infantes quidem possint.</li>
<li>Etenim semper illud extra est, quod arte comprehenditur.</li>
</ul>
<p><b>Hoc loco tenere se Triarius non potuit.</b> Utinam quidem dicerent alium alio beatiorem! Iam ruinas videres. Quid enim necesse est, tamquam meretricem in matronarum coetum, sic voluptatem in virtutum concilium adducere? <b>Sed ille, ut dixi, vitiose.</b> Haec bene dicuntur, nec ego repugno, sed inter sese ipsa pugnant. Hic ambiguo ludimur. </p>
<p>Duo Reges: constructio interrete. Qui non moveatur et offensione turpitudinis et comprobatione honestatis? Quam ob rem tandem, inquit, non satisfacit? Et quod est munus, quod opus sapientiae? Egone quaeris, inquit, quid sentiam? <mark>Memini vero, inquam;</mark> Videsne, ut haec concinant? Quos quidem tibi studiose et diligenter tractandos magnopere censeo. </p>
EOL;

    protected static $_markdown = <<<EOL
# Lorem ipsum dolor sit amet

## consectetur adipiscing elit. 

### Nulla erit controversia.

1.  Itaque rursus eadem ratione, qua sum paulo ante usus, haerebitis.
2.  In motu et in statu corporis nihil inest, quod animadvertendum esse ipsa natura iudicet?
3.  Sed eum qui audiebant, quoad poterant, defendebant sententiam suam.
4.  Cur post Tarentum ad Archytam?

*   Ita multo sanguine profuso in laetitia et in victoria est mortuus.
*   Quid loquor de nobis, qui ad laudem et ad decus nati, suscepti, instituti sumus?
*   Nam adhuc, meo fortasse vitio, quid ego quaeram non perspicis.
*   Deinde disputat, quod cuiusque generis animantium statui deceat extremum.

At, si voluptas esset bonum, desideraret. Venit ad extremum; Illum mallem levares, quo optimum atque humanissimum virum, Cn. Sed quid sentiat, non videtis. _Facete M._ Non ego tecum iam ita iocabor, ut isdem his de rebus, cum L.

Mihi, inquam, qui te id ipsum rogavi? Mihi enim erit isdem istis fortasse iam utendum. Videsne quam sit magna dissensio? Aliud igitur esse censet gaudere, aliud non dolere. Optime, inquam. Duo enim genera quae erant, fecit tria. Quis enim potest ea, quae probabilia videantur ei, non probare? <mark>Refert tamen, quo modo.</mark> Quae cum essent dicta, finem fecimus et ambulandi et disputandi. Quis tibi ergo istud dabit praeter Pyrrhonem, Aristonem eorumve similes, quos tu non probas? **Quis enim redargueret?**
EOL;

    protected static $_geoLocation = [
        'address'           => 'Prins Hendrikstraat 91',
        'latitude'          => '52.1230261',
        'longitude'         => '4.662420099999963',
        'formatted_address' => 'Prins Hendrikstraat 91, 2405 AH Alphen aan den Rijn, Netherlands',
    ];

    protected static $_video = [
        'url'        => 'https://www.youtube.com/watch?v=x4IDM3ltTYo',
        'width'      => '854', 'height' => '480',
        'title'      => 'Silversun Pickups - Nightlight (Official Video)',
        'authorname' => 'Silversun Pickups',
        'ratio'      => '1.7791666666666666',
        'authorurl'  => 'https://www.youtube.com/user/Silversunpickups',
        'html'       => '<iframe class="embedly-embed" src="//cdn.embedly.com/widgets/media.html?src=https%3A%2F%2Fwww.youtube.com%2Fembed%2Fx4IDM3ltTYo%3Ffeature%3Doembed&url=http%3A%2F%2Fwww.youtube.com%2Fwatch%3Fv%3Dx4IDM3ltTYo&image=https%3A%2F%2Fi.ytimg.com%2Fvi%2Fx4IDM3ltTYo%2Fhqdefault.jpg&key=3fedecb044d94eccb9eef404bee82126&type=text%2Fhtml&schema=youtube" width="854" height="480" scrolling="no" frameborder="0" allowfullscreen></iframe>',
        'thumbnail'  => 'https://i.ytimg.com/vi/x4IDM3ltTYo/hqdefault.jpg',
    ];

    protected static $_image = [
        'file'  => 'agriculture-cereals-field-621.jpg',
        'title' => 'Wheat field',
    ];

    protected static $_imageList = [
        ['filename' => 'carrot-cooking-eat-1398.jpg', 'title' => 'Carrot'],
        ['filename' => 'blur-breakfast-coffee-271.jpg', 'title' => 'Breakfast & coffee'],
    ];

    protected static $_file = 'index.html';

    protected static $_fileList = [
        ['filename' => 'garden-gardening-grass-589.jpg', 'title' => 'Gardening grass'],
        ['filename' => 'food-fruit-orange-1286.jpg', 'title' => 'Oranges'],
    ];

    /**
     * @return Content
     */
    public static function getTestEntity()
    {
        $entity = new Content();

        $entity->setSlug('koala-company');
        $entity->setDatecreated(Carbon::now());
        $entity->setDatechanged(Carbon::now());
        $entity->setOwnerid(1);
        $entity->setStatus('published');
        $entity->setTemplatefields([]);
        $entity->set('title', 'Does your koala pass the test?');
        $entity->set('html', static::$_html);
        $entity->set('textarea', static::$_textArea);
        $entity->set('markdown', static::$_markdown);
        $entity->set('geolocation', static::$_geoLocation);
        $entity->set('video', static::$_video);
        $entity->set('image', static::$_image);
        $entity->set('imagelist', static::$_imageList);
        $entity->set('file', static::$_file);
        $entity->set('filelist', static::$_fileList);
        $entity->set('checkbox', true);
        $entity->set('datetime', Carbon::create(2012, 6, 14, 9, 7, 55));
        $entity->set('date', Carbon::create(2012, 6, 14));
        $entity->set('integerfield', -1555);
        $entity->set('floatfield', 3.1415);
        $entity->set('selectfield', 'nightlight');
        $entity->set('multiselect', ['Brian Aubert', 'Christopher Guanlao', 'Joe Lester', 'Nikki Monninger']);
        $entity->set('selectentry', '1');

        return $entity;
    }
}
