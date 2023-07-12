<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\SecurityBundle\Security;
use App\Entity\Package;
use App\Entity\User;
use App\Form\PackageType;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Mercure\HubInterface;
use Dompdf\Dompdf;
use Symfony\Component\Mercure\Update;
class PackageController extends AbstractController
{
    private $security;
    private $entityManager;

    public function __construct(Security $security,ManagerRegistry $doctrine)
    {
        
        // Avoid calling getUser() in the constructor: auth may not
        // be complete yet. Instead, store the entire Security object.
        $this->security = $security;
        $this->entityManager = $doctrine->getManager();
        $user = $this->security->getUser();
        if(!$user){
            return $this->RedirectToRoute('app_login');    
        }
    }
    #[Route('/package', name: 'app_package')]
    public function index(): Response
    {

        $user = $this->security->getUser();
        if(!$user){
            return $this->RedirectToRoute('app_login');    
        }
     
        if (in_array('EXPEDITOR_ROLE', $user->getRoles(), true)) {

            
            $packages = $user->getPackages();
        
            return $this->render('Expeditor/package/index.html.twig', [
                'packages' => $packages->toArray(),
            ]);
        }
        if (in_array('ADMIN_ROLE', $user->getRoles(), true)) {

          $packages =  $this->entityManager->getRepository(Package::class)->findAllForAdmin();
            return $this->render('Admin/package/index.html.twig', [
                'packages' => $packages,
            ]); 
        }
        
        if (in_array('DELIVERY_ROLE', $user->getRoles(), true)) {

            $packages =  $this->entityManager->getRepository(Package::class)->findAllForDelivery($user->getId());
              return $this->render('Delivery/package/index.html.twig', [
                  'packages' => $packages,
              ]); 
          }


    }

    #[Route('/package/add', name: 'app_add_package')]
    public function add(Request $req): Response
    {
        $user = $this->security->getUser();
        $pack = new Package();

        $form = $this->createForm(PackageType::class,$pack);
        $form->handlerequest($req);
        if($form->isSubmitted()  && $form->isValid()){
            $pack->setState("waiting");
            $date = new \DateTime('@'.strtotime('now'));
        
            $pack->setCreateDate( $date);
            $pack->setExpeditor( $user);
            $this->entityManager->persist($pack);
            $this->entityManager->flush();
          return  $this->redirectToRoute('app_package',array('msg' => "Package added successfuly"));
        }
        return $this->render('expeditor/package/add.html.twig', [
            'form' => $form->createView(),
        ]); 


    }


    #[Route('/package/validate/{id}', name: 'app_validate_package')]
    public function validate(Request $req,$id,HubInterface $hub): Response
    {
       $pack = $this->entityManager->getRepository(Package::class)->find($id);
       if($pack){
        $pack->setState("approved");
        $this->entityManager->persist($pack);
        $this->entityManager->flush();
        $update = new Update(
            'https://example.com/books/1',
            json_encode(['message' => 'New package has been approved ID : '.$id])
        );

        $hub->publish($update);
        return $this->redirectToRoute("app_package",["msg"=>"Your package approved successfuly"]);
       }else{
        return $this->redirectToRoute("app_package",["error"=>"The package is not disponible"]);
       }
        
    }
    #[Route('/package/picked/{id}', name: 'app_picked_package')]
    public function picked(Request $req,$id): Response
    {
       $pack = $this->entityManager->getRepository(Package::class)->find($id);
       if($pack){
        $pack->setState("picked");
        $this->entityManager->persist($pack);
        $this->entityManager->flush();
        return $this->redirectToRoute("app_package",["msg"=>"Your package approved successfuly"]);
       }else{
        return $this->redirectToRoute("app_package",["error"=>"The package is not disponible"]);
       }
        
    }

    #[Route('/package/edit/{id}', name: 'app_edit_package')]
    public function edit(Request $req,$id): Response
    {
       $pack = $this->entityManager->getRepository(Package::class)->find($id);
   
       $form = $this->createForm(PackageType::class,$pack);
       $form->handlerequest($req);
       if($form->isSubmitted()  && $form->isValid()){
        $p = new Package();
        $p = $form->getData();
         $this->entityManager->getRepository(Package::class)->save($p,true);
         
         return  $this->redirectToRoute('app_package',array('msg' => "Package edited successfuly"));
       }
       return $this->render('expeditor/package/edit.html.twig', [
           'form' => $form->createView(),
       ]); 
        
    }
    #[Route('/package/delivery/{id}', name: 'app_delivery_package')]
    public function delivery(Request $req,$id): Response
    {
       $pack = $this->entityManager->getRepository(Package::class)->find($id);
       $delivery = $this->entityManager->getRepository(User::class)->findByRole("DELIVERY_ROLE");
   
       $form = $this->createForm(PackageType::class,$pack);
       $form->handlerequest($req);
       if($form->isSubmitted()  && $form->isValid()){
        $p = new Package();
        $p = $form->getData();
         $this->entityManager->getRepository(Package::class)->save($p,true);
         
         return  $this->redirectToRoute('app_package',array('msg' => "Package edited successfuly"));
       }
       return $this->render('admin/package/edit.html.twig', [
           'form' => $form->createView(),
           "delivery"=>$delivery
       ]); 
        
    }
    #[Route('/package/pdf/{id}', name: 'app_pdf_package')]
    public function pdf(Request $req,$id):Response
    {
        
        
        $html = $this->renderView('pdf/package.html.twig', [
            'specifications' => "",
        ]);
        $dompdf = new Dompdf();
        $dompdf->load_html($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render(); 
        $dompdf->stream("sample.pdf");
       
        return new Response('', 200, [
            'Content-Type' => 'application/pdf',
          ]);
        
    }
    #[Route('/package/delete/{id}', name: 'app_delete_package')]
    public function delete(Request $req,$id): Response
    {
       $pack = $this->entityManager->getRepository(Package::class)->find($id);
       if($pack){
        $this->entityManager->remove($pack);
        $this->entityManager->flush();
        return $this->redirectToRoute("app_package",["msg"=>"Your package deleted successfuly"]);
       }else{
        return $this->redirectToRoute("app_package",["error"=>"The package is not disponible"]);
       }
        
    }
}
