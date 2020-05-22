<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Article;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use PhpParser\Node\Stmt\Label;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;

use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\Validator\Constraints\Unique;
use App\Entity\Comment;
use Symfony\Component\BrowserKit\Request as SymfonyRequest;

class BlogController extends AbstractController
{
    /**
     * @Route("/", name="blog_index") //modiffier retirer le blog 
     */


    public function index()
    {
        //on récupére un repository il sert a envoyer des requet de l apart de doctrine qu'on a besoin il va demandé 
        //au repository de fair les choses pour lui
        //pour pouvoir communiquer avec la bas de données via doctrine
        //il faut faire appel au Repository en charge de l'objet à manipuler
        $articleRepository = $this->getDoctrine()->getRepository(Article::class);
        //on demande ensuite à ce repository de récupérer nos article en bdd
        $articles = $articleRepository->findAll(); //la on récuper nos articles

        return $this->render('blog/index.html.twig', [ //on fait un tableau 
            'articles' => $articles
        ]); //template écrite avec des twig 
    }
    /**
     * @Route("/view/{id}", name="blog_view", requirements={
     * "id" = "\d+"
     * }) //on ajoute des préruqués requirement il faut qu'il sera un nombre
     */



    public function view($id) //charger d'afficher un seul article
    {
        //on va chercher les articles 
        $articleRepository = $this->getDoctrine()->getRepository(Article::class);
        $article = $articleRepository->find($id);
        if (is_null($article)) {
            return $this->redirectToRoute('blog_not_found');
        }
        $comments = $article->getComments();
        return $this->render('blog/view.html.twig', [
            'article' => $article,
            'comments' => $comments
        ]); //le lien vers nos template
    }
    /**
     * @Route("/add", name="blog_add")
     */



    public function add(Request $request)
    {
        //on refuse l'accès au utilisateurs non connécter
        $this->denyAccessUnlessGranted(('IS_AUTHENTICATED_REMEMBERED')); // sans remembred il demande de réconnecter
        //on récupère l'utilisateur connécté 
        $user = $this->getUser();
        //création d'une entité
        $article = new Article();
        $article->setSubmitDate(new \DateTime());
        $article->setAuthor($user);

        //on prépare notre formulaire à l'aide de formBuilder
        //on renseigne quelle entité sera concernée par notre formulaire 
        $form = $this->createFormBuilder($article) //on va lui demander de rendre une form et on va lui demané de rendre des champs
            ->add('title', TextType::class)
            ->add('content', TextareaType::class)
            ->add('image', FileType::class, ['label' => "Image (JPG, PNG)"])
            ->add('submit', SubmitType::class, ['label' => "Poster Article"])
            ->getForm(); //récupérer formulair
        //on demande au formulaire de traiter la requete HTTP
        $form->handleRequest($request); //on lui passe la requete pour qu'il puiss la lire
        //si on est en POST et si l formulaire est valide
        if ($form->isSubmitted() && $form->isValid()) {
            //alors on peut traiter l'enregistrement des données dans la base
            //on commence le traitement du téléchargement de l'image
            //d'abbord on récupére les données de l'image
            $file = $form->get('image')->getData();
            //ensuite on génére un nom pour cette image
            //on hache un identifiant unique pour avoir une string unique
            $uniqueName = md5(uniqid()); //md5 une fonction de hashach 
            //on génére ensuite le nom de fichier avec son extension 
            $filename = $uniqueName . '.' . $file->guessExtension(); //pour savoir si il est jgp ou pgphff
            //on va essayer d'enregister l'image dans notre sérveur
            try {
                $file->move(
                    $this->getParameter('image_directory'),
                    $filename
                );
            } catch (FileException $exception) { }
            //on enregistre ensuite le chemin du fichier dans notre article
            $article->setImage($filename);
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($article);
            $entityManager->flush();

            return $this->redirectToRoute('blog_view', [
                'id' => $article->getId()
            ]);
        }


        return $this->render('blog/add.html.twig', [
            'form' => $form->createView()
        ]);
    }

    public function comment($article)
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        $user = $this->getUser();
        $comment = new Comment();
        $comment->setAuthor($user);
        $comment->setSubmitDate(new \DateTime());
        $comment->setArticle($article);


        $form = $this->createFormBuilder($comment)
            ->add('content', TextareaType::class)
            ->add('submit', SubmitType::class, ['label' => 'commenter'])
            ->getForm();
        $form->handleRequest($this->get('request_stack')->getMasterRequest());

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($comment);
            $entityManager->flush();
        }
        return $this->render('blog/_commentForm.html.twig', [ //pas senci d'etre utiliser
            'commentForm' => $form->createView()
        ]);
    }
    /**
     * @Route("/notFound", name ="blog_not_found")
     */
    public function notFound()
    {
        return $this->render(('blog/notFound.html.twig'));
    }


    /**
     * @Route("/blog/delete/{id}", name="blog_delete", requirements={"id" = "\d+"})
     */
    public function delete($id)
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        $user = $this->getUser();

        $articleRepository = $this->getDoctrine()->getRepository(Article::class);
        $article = $articleRepository->find($id);

        if (is_null($article)) {
            return $this->redirectToRoute('blog_not_found');
        }

        if ($article->getAuthor()->getUsername() != $user->getUsername()) {
            return $this->redirectToRoute('blog_view', [
                'id' => $article->getId()
            ]);
        }


        $entityManager = $this->getDoctrine()->getManager();
        //d'abord supprimer les commentaires de l'article
        $comments = $article->getComments();
        foreach ($comments as $comment) {
            $entityManager->remove($comment);
        }
        $entityManager->remove($article);
        $entityManager->flush();
        return $this->redirectToRoute('blog_index');
    }

    /**
     *@Route("/blog/edit/{id}", name="blog_edit", requirements={"id" = "\d+"})
     */
    public function edit($id, Request $request)
    {
        //refuser l'accès aux utilisateurs non connectés
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        $user = $this->getUser();

        $articleRepository = $this->getDoctrine()->getRepository(Article::class);
        $article = $articleRepository->find($id);

        if (is_null($article)) {
            return $this->redirectToRoute('blog_not_found');
        }

        //si l'auteur de l'article ne correspond pas a l'utilisateur connecté
        if ($article->getAuthor()->getUsername() != $user->getUsername()) {
            //on redirige
            return $this->redirectToRoute(
                'blog_view',
                [
                    'id' => $article->getId()
                ]
            );
        }

        $form = $this->createFormBuilder($article)
            ->add('title', TextType::class)
            ->add('content', TextareaType::class)
            ->add('image', FileType::class, ['label' => 'Poster nouvelle image', 'mapped' => false, 'required' => false])
            ->add('submit', SubmitType::class, ['label' => 'Valider'])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            //alors on peut traiter l'enregistrement des données dans la base
            //on commence le traitement du téléchargement de l'image
            //d'abord on récupère les données de l'image
            $file = $form->get('image')->getData();

            //si on a bien reçu un fichier
            if (!is_null($file)) {
                //ensuite on génère un nom pour cette image
                //on hache un identifiant unique pour avoir une string unique
                $uniqueName = md5(uniqid());
                //on génère ensuite le nom de fichier avec son extension
                $filename = $uniqueName . '.' . $file->guessExtension();

                //on va maintenant essayer d'enregistrer l'image sur notre serveur
                try {
                    $file->move(
                        $this->getParameter('image_directory'),
                        $filename
                    );
                } catch (FileException $exception) {
                    //TODO gérer l'erreur de fichier
                }
                //on enregistre ensuite le chemin du fichier dans notre article
                $article->setImage($filename);
            }
            //on termine les opérations avec doctrine
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('blog_view', [
                "id" => $article->getId()
            ]);
        }

        return $this->render('blog/edit.html.twig', [
            'edit_form' => $form->createView()
        ]);
    }
}
