<?php

namespace App\Controller;


use App\Entity\Facture;
use App\Entity\Package;
use App\Entity\User;
use App\Entity\Notification;
use Dompdf\Dompdf;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class FactureController extends AbstractController
{
    private $security;
    private $entityManager;
    private $user;

    public function __construct(Security $security, ManagerRegistry $doctrine)
    {
        $this->security = $security;
        $this->entityManager = $doctrine->getManager();
        $this->user= $this->security->getUser();
       
    }
    #[Route('/invoice', name: 'app_invoice')]
    public function index(): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED');
        $user = $this->security->getUser();  
        $factures=[];
        if (in_array("EXPEDITOR_ROLE",$user->getRoles())) {
           



$factures = $this->entityManager->getRepository(Facture::class)->findFactureByExpeditor($user->getId());


        }elseif(in_array("ADMIN_ROLE",$user->getRoles())){
            
$factures = $this->entityManager->getRepository(Facture::class)->findFactureByAdmin($user->getAccount()->getId());

        }
        return $this->render('facture/index.html.twig', [
            'factures' => $factures,
        ]);
    }

    #[Route('/invoice/demande', name: 'app_demande_invoice')]
    public function demande(): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED');
        $user = $this->security->getUser();
        $fact = new Facture();

        $lastFacture = $this->entityManager->getRepository(Facture::class)->findOneBy(
            [],
            ['createAt' => 'DESC']
        );
       

        $lastFactureId = $lastFacture ? $lastFacture->getId() : 0;
        $packages = $this->entityManager->getRepository(Package::class)->findAllForExpeditorInvoice($user->getId());

        if($packages == null || empty($packages)){
            return $this->redirectToRoute('app_invoice', array('error' => "There is not any package need an invoice"));
        }
       
        return $this->render('facture/add.html.twig', [
            'packages' => $packages,
            "invoiceId" => $lastFactureId + 1
        ]);
    }
    #[Route('/invoice/add', name: 'app_add_invoice')]
    public function add(): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED');
        $user = $this->security->getUser();

        $packages = $this->entityManager->getRepository(Package::class)->findAllForExpeditorInvoice($user->getId());
        
        if($packages == null || empty($packages)){
            return $this->redirectToRoute('app_invoice', array('error' => "There is not any package need an invoice"));
        }
        $tax = 0;
        $price = 0;
       
        $fact = new Facture();
   
        foreach ($packages as $key => $pack) {
            if ($pack->getState() == 'returned') {
                $tax += $pack->getExpeditor()->getFraisDeRetour();
                $price += 0;
            } else {
                $tax += $pack->getExpeditor()->getFraisDeLivraison();
                $price += $pack->getPrice();

            }

            $fact->addPackage($pack);

        }

        $fact->setExpeditor($user);
        $fact->setPrice($price);
        $fact->setTax($tax);
        $fact->setState('processing');
        $fact->setCreateAt(new \DateTime('@' . strtotime('now')));
        $this->entityManager->persist($fact);
        $this->entityManager->flush();

        $admin = $this->entityManager->getRepository(User::class)->findByRoleByAccount("ADMIN_ROLE",$user->getAccount()->getId());
  
        $notif  = new Notification();
        $notif->setTitle("A new invoice request");
        $notif->setMessage("A new invoice request  from ".$fact->getExpeditor()->getName()." if you want to see it <a href='/invoice/pdf/".$fact->getId()."'>click here</a>");
        $notif->setUser($admin);
        $notif->setIcon('<i class="fas fa-file-invoice-dollar"></i>');
        $notif->setCreatedAt(new \DateTime('@' . strtotime('now')));
        $notif->setSeen(0);
        $this->entityManager->persist($notif);
      
    
        $this->entityManager->flush();
        
        return $this->redirectToRoute('app_invoice', array('msg' => "Invoice added successfuly #".$fact->getId()));

    }
    #[Route('/invoice/validate/{id}', name: 'app_validate_invoice')]
    public function validate($id): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED');
        $user = $this->security->getUser();

        $facture = $this->entityManager->getRepository(Facture::class)->find($id);
      if($facture){
        $facture->setState("done");
        $facture->setClosedAt(new \DateTime('@' . strtotime('now')));
        $this->entityManager->persist($facture);
        $this->entityManager->flush();
        return $this->redirectToRoute('app_invoice', array('msg' => "Invoice Closed successfuly #".$facture->getId()));

      }else{
        return $this->redirectToRoute('app_invoice', array('error' => "No invoice found"));
      }

      

    }

    #[Route('/invoice/pdf/{id}', name: 'app_pdf_invoice')]
    public function pdf( $id): Response
    {


        $f =  $this->entityManager->getRepository(Facture::class)->find($id);

        
        $photo = ($f->getExpeditor()->getPhoto() != null) ? 'upload/img/' . $f->getExpeditor()->getPhoto() : "img/profile.png";

        $data = [
            'profile'  => $this->imageToBase64($this->getParameter('kernel.project_dir') . '/public/' . $photo),
            'logo'  => $this->imageToBase64($this->getParameter('kernel.project_dir') . '/public/img/logoamena.png'),
            
            'facture' => $f
        ];
        //   return  $this->render('pdf/package.html.twig',$data);
        $html = $this->renderView('pdf/invoice.html.twig', $data);


        $dompdf = new Dompdf();

        $dompdf->load_html($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();
        $dompdf->stream("facture_".$f->getId().".pdf");

        return new Response('', 200, [
            'Content-Type' => 'application/pdf',
        ]);
    }
    private function imageToBase64($path)
    {
        $path = $path;
        $type = pathinfo($path, PATHINFO_EXTENSION);
        $data = file_get_contents($path);
        $base64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
        return $base64;
    }
}