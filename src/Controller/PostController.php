<?php

namespace App\Controller;

use App\Entity\Post;
use App\Entity\User;
use App\Entity\Interaction;
use App\Form\PostType;
use App\Form\InteractionFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Knp\Component\Pager\PaginatorInterface;

use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class PostController extends AbstractController
{


    private $em;
    /**
     * @param $em 
     */   

    public function __construct(EntityManagerInterface $em)
    {
            $this->em = $em;
    }

    /*
    //Create Post
    #[Route('/insert/post', name: 'insert_post')]
    public function insert()
    {

            $post = new Post();
            $user= $this->em->getRepository(User::class)->find(1);
            $post->setUser($user)
                 ->setTitle("Nuevo Titulo")
                 ->setType("Opinion")
                 ->setDescription("Nueva Descripcion")
                 ->setFile("file.jpg")
                 ->setCreationDate(new \datetime())
                 ->setUrl("www.google.com");
            $this->em->persist($post);
            $this->em->flush();
            
            return new JsonResponse(['success' => true]);
    }

    //Read Post
    #[Route('/post', name: 'read_post')]
    public function index(): Response
    {
            $post = $this->em->getRepository(Post::class)->findAllPost();            
            return $this->render('post/index.html.twig',
            [
            'post' => $post
            ]
        );
    }

    //Update Post
    #[Route('/update/{id}', name: 'update_post')]
    public function update($id)
    {
            $post = $this->em->getRepository(Post::class)->find($id);
            $post->setTitle("Testing");
            $this->em->flush();

            return new JsonResponse(['success' => true]);
        
    }

    //Delete Post
    #[Route('/delete/{id}', name: 'delete_post')]
    public function delete($id)
    {
            $post = $this->em->getRepository(Post::class)->find($id);
            $this->em->remove($post);
            $this->em->flush();

            return new JsonResponse(['success' => true]);
        
    }
    */

    #[Route('/', name: 'index')]
    public function index(Request $request, SluggerInterface $slugger, PaginatorInterface $paginator, AuthenticationUtils $authenticationUtils): Response
    {
        $post = new Post();
        $query = $this->em->getRepository(Post::class)->findAllPost();   
        
        $pagination = $paginator->paginate(
            $query, /* query NOT result */
            $request->query->getInt('page', 1), /*page number*/
            5 /*limit per page*/
        );

        $form = $this->createForm(PostType::class, $post);
        $form->handleRequest($request);
        if ( $form->isSubmitted() && $form->isValid()) {
            $file = $form->get('file')->getData();
            $url = str_replace(" ", "-", $form->get('title')->getData());

            if ( $file ) {
                $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$file->guessExtension();
                try {
                    $file->move(
                        $this->getParameter('files_directory'),
                        $newFilename
                    );
                } catch (FileException $e) {
                    throw new \Exception('Ups there is a problem with your file');
                }

                $post->setFile($newFilename);
            }

            $post->setUrl($url);

            $email_auth = $authenticationUtils->getLastUsername();
            $user_entity = $this->em->getRepository(User::class)->findOneBy(['email' => $email_auth]);
            $post->setUser($user_entity);
            $this->em->persist($post);
            $this->em->flush();
            return $this->redirectToRoute('index');
        }

        return $this->render('post/index.html.twig', [
            'form' => $form->createView(),
            'posts' => $pagination            
        ]);
    }


    #[Route('/post/details/{id}', name: 'postDetails')]
    public function postDetails($id, Request $request, AuthenticationUtils $authenticationUtils)
    {  
        
        $interaction = new Interaction();
        $registration_form = $this->createForm(InteractionFormType::class, $interaction);
        $registration_form->handleRequest($request);
        

        if ($registration_form->isSubmitted() && $registration_form->isValid()) {    
                       
            $email_auth = $authenticationUtils->getLastUsername();
            $user_entity = $this->em->getRepository(User::class)->findOneBy(['email' => $email_auth]);
            $post_entity = $this->em->getRepository(Post::class)->find($id);

            $comment = $registration_form->get('comment')->getData();

            $interaction->setComment($comment)
                        ->setUser($user_entity)
                        ->setPost($post_entity)                 
                        ->setUserFavorite(1);
            $this->em->persist($interaction);
            $this->em->flush();     

            return $this->redirectToRoute('postDetails', ['id' => $id, 'user_entity' => $user_entity]);
        }
        
        $post = $this->em->getRepository(Post::class)->findPost($id);        
        $interactions = $this->em->getRepository(Interaction::class)->interactionPost($id);             

        return $this->render('post/interactions.html.twig', 
        ['interactions' => $interactions,  'post' => $post, 'registration_form' => $registration_form->createView()]);
       
    }
    /*
    #[Route('/agg_interaction/{id}/{title}', name: 'agg_interaction')]
    public function agg_interaction($id,$title) {
       
        // saber cual es el user
        $user= $this->em->getRepository(User::class)->find(6);
        $post= $this->em->getRepository(Post::class)->find($id);
            $interaction = new Interaction();  
            $interaction->setComment($title)
                        ->setUser($user)
                        ->setPost($post)                 
                        ->setUserFavorite(1);
            $this->em->persist($interaction);
            $this->em->flush();        
        //
        return $this->redirectToRoute('postDetails', ['id' => $id]);
    }
    */
    /*
    #[Route('/show', name: 'show')]
    public function new(Request $request): Response
    {

        $interaction = new Interaction();
        $registration_form = $this->createForm(InteractionFormType::class, $interaction);
        $registration_form->handleRequest($request);

        if ($registration_form->isSubmitted() && $registration_form->isValid()) {    

            $user= $this->em->getRepository(User::class)->find(6);
            $post= $this->em->getRepository(Post::class)->find(17);
            $comment = $registration_form->get('comment')->getData();

            $interaction->setComment($comment)
                        ->setUser($user)
                        ->setPost($post)                 
                        ->setUserFavorite(1);
            $this->em->persist($interaction);
            $this->em->flush();     

            return new Response('TODO PIOLA');

        }

        return $this->render('interaction/index.html.twig', [
            'registration_form' => $registration_form->createView()
        ]);
    }
    */
    
}

