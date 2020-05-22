<?php

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;
use App\Entity\Article;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

class ArticleFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager)
    {
        for($i = 1; $i <= 15; $i++){
            $article = new Article();
            $article->setTitle('Article' .$i);
            $article->setContent('« Texte » est issu du mot latin « textum », 
            dérivé du verbe « texere » qui signifie « tisser ». 
            Le mot s applique à l entrelacement des fibres utilisées dans le tissage, 
            voir par exemple Ovide : « Quo super iniecit textum rude sedula Baucis 
            (un siège) sur lequel Baucis empressée avait jeté un tissu grossier »2 ou au tressage
             (exemple chez Martial « Vimineum textum = panier d osier tressé »).
              Le verbe a aussi le sens large de construire comme dans « basilicam 
              texere = construire une basilique » chez Cicéron3.

            Le sens figuré d éléments de langage organisés et enchaînés apparaît avant lEmpire romain :
             il désigne un agencement particulier du discours. Exemple : 
             « epistolas texere = composer des épîtres » - Cicéron (Ier siècle av. J.-C.)4 
             ou plus nettement chez Quintilien (Ier siècle apr. J.-C.) : « verba in textu jungantur =
              l agencement des mots dans la phrase »5.
            
            Les formes anciennes du Moyen Âge désignent au XIIe siècle le volume qui contient le texte
             sacré des Évangiles, puis au XIIIe siècle. le texte original d un livre saint ou des propos 
             de quelqu un. Au XVIIe siècle le mot s’applique au passage d un ouvrage pris comme référence 
              au début du XIXe siècle le mot texte a son sens général d écrit' );
            $article->setImage('default.jpg');
            $article->setSubmitDate(new \DateTime());
            $article->setAuthor($this->getReference('user' .$i));
            $this->addReference('article' .$i, $article);
            $manager->persist($article);
        }

        $manager->flush();
    }
    public function getDependencies()
    {
        return [UserFixtures::class];
    }
}
