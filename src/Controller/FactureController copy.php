<?php

namespace App\Controller;


use App\Entity\Facture;
use App\Entity\Package;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class FactureController extends AbstractController
{
    private $security;
    private $entityManager;

    public function __construct(Security $security, ManagerRegistry $doctrine)
    {
        $this->security = $security;
        $this->entityManager = $doctrine->getManager();

    }
    #[Route('/invoice', name: 'app_invoice')]
    public function index(): Response
    {

        $factures = $this->entityManager->getRepository(Facture::class)->findAll();
        return $this->render('facture/index.html.twig', [
            'factures' => $factures,
        ]);
    }

    #[Route('/invoice/demande', name: 'app_demande_invoice')]
    public function demande(): Response
    {
        $user = $this->security->getUser();
        $fact = new Facture();

        $lastFacture = $this->entityManager->getRepository(Facture::class)->findOneBy(
            [],
            ['createAt' => 'DESC']
        );
        $lastFactureId = $lastFacture ? $lastFacture->getId() : 0;
        $packages = $this->entityManager->getRepository(Package::class)->findAllForExpeditorInvoice($user->getId());


        return $this->render('facture/add.html.twig', [
            'packages' => $packages,
            "invoiceId" => $lastFactureId + 1
        ]);
    }
    #[Route('/invoice/add', name: 'app_add_invoice')]
    public function add(): Response
    {
        $user = $this->security->getUser();

        $packages = $this->entityManager->getRepository(Package::class)->findAllForExpeditorInvoice($user->getId());
        if($packages == null || empty($packages)){
            return $this->redirectToRoute('app_invoice', array('error' => "There is no package need an invoice"));
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
        return $this->redirectToRoute('app_invoice', array('msg' => "Invoice added successfuly #".$fact->getId()));

    }
    #[Route('/invoice/validate/{id}', name: 'app_validate_invoice')]
    public function validate($id): Response
    {
        $user = $this->security->getUser();

        $facture = $this->entityManager->getRepository(Facture::class)->find($id);
      if($facture){
        $facture->setState("Done");
        $facture->setClosedAt(new \DateTime('@' . strtotime('now')));
        $this->entityManager->persist($facture);
        $this->entityManager->flush();
        return $this->redirectToRoute('app_invoice', array('msg' => "Invoice Closed successfuly #".$facture->getId()));

      }else{
        return $this->redirectToRoute('app_invoice', array('error' => "No invoice found"));
      }

      

    }

    #[Route('/invoice/pdf/{id}', name: 'app_pdf_invoice')]
    public function pdf(Request $req, $id): Response
    {


        $f =  $this->entityManager->getRepository(Facture::class)->find($id);

  
        //   return  $this->render('pdf/package.html.twig',$data);
        $html = $this->renderView('pdf/invoice.html.twig', ['facture' => $f]);


        $dompdf = new Dompdf();

        $dompdf->load_html($html);

        $dompdf->render();
        $dompdf->stream("facture_".$f->id.".pdf");

        return new Response('', 200, [
            'Content-Type' => 'application/pdf',
        ]);
    }
}