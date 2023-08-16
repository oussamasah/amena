<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\SecurityBundle\Security;
use Doctrine\Persistence\ManagerRegistry;
use App\Entity\Package;
use App\Entity\User;
use App\Entity\Facture;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
class DashbordController extends AbstractController
{
    private $security;
    private $entityManager;
    private $chart;
    private  $user;
    public function __construct(Security $security,ManagerRegistry $doctrine,ChartBuilderInterface $chartBuilder)
    {
        $this->entityManager = $doctrine->getManager();
        $this->security = $security;
        $this->chart = $chartBuilder;
     
        $this->user = $this->security->getUser();
 
    }
    #[Route('/dashboard', name: 'app_dashbord')]
    public function index( Request $req): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED');


        if($req->request->get('debut') == null){
            $debut = date('Y-m-d', strtotime('-30 days'));
          
        }
        if($req->request->get('fin') == null){
            $fin = date('Y-m-d');
        }

   
        if (in_array('EXPEDITOR_ROLE',  $this->user->getRoles(), true)) {

           $waiting = $this->entityManager->getRepository(Package::class)->getWithState("waiting", $this->user->getId(),$debut,$fin);
           $approved = $this->entityManager->getRepository(Package::class)->getWithState("approved", $this->user->getId(),$debut,$fin);
           $picked = $this->entityManager->getRepository(Package::class)->getWithState("picked", $this->user->getId(),$debut,$fin);
           $delivered = $this->entityManager->getRepository(Package::class)->getWithState("delivered", $this->user->getId(),$debut,$fin);
           $returned = $this->entityManager->getRepository(Package::class)->getWithState("returned", $this->user->getId(),$debut,$fin);

            return $this->render('Expeditor/dashbord/index.html.twig', [
                'waiting' => $waiting,
                'approved' => $approved,
                'picked' => $picked,
                'delivered' => $delivered,
                'returned' => $returned,
            ]);   
        }
        if (in_array('ADMIN_ROLE',  $this->user->getRoles(), true)) {


            $waiting = $this->entityManager->getRepository(Package::class)->getWithStateforAdmin("waiting", $this->user->getAccount()->getId(),$debut,$fin);
            $approved = $this->entityManager->getRepository(Package::class)->getWithStateforAdmin("approved", $this->user->getAccount()->getId(),$debut,$fin);
            $picked = $this->entityManager->getRepository(Package::class)->getWithStateforAdmin("picked", $this->user->getAccount()->getId(),$debut,$fin);
            $delivered = $this->entityManager->getRepository(Package::class)->getWithStateforAdmin("delivered", $this->user->getAccount()->getId(),$debut,$fin);
            $returned = $this->entityManager->getRepository(Package::class)->getWithStateforAdmin("returned", $this->user->getAccount()->getId(),$debut,$fin);
            
            $expeditors  = $this->entityManager->getRepository(User::class)->findBybyRoleByAccount("EXPEDITOR", $this->user->getAccount()->getId());
            $dataPackagesExpeditor=[];
            $dataPackagesFacturesDone=[];
            $dataPackagesFacturesProcessing=[];
            $totalPrice=0;
            $totalTax=0;
            $TotalPackageExpeditor=0;
            foreach ($expeditors as $key => $exp) {
                $dataPackagesExpeditor["labels"][]= $exp->getName();
                $pkgs = $this->entityManager->getRepository(Package::class)->findPackagesCountByExpeditorAndDateRange($exp->getId(),$debut,$fin);
                
                $dataPackagesExpeditor["data"][]= count($pkgs);
                $TotalPackageExpeditor+=count($pkgs);
                $factures = $this->entityManager->getRepository(Facture::class)->findFactureCountByExpeditorAndDateRange($exp->getId(),$debut,$fin);
                $sum = 0;
                $tax = 0;
                foreach ($factures as $key => $fact) {
                    $sum+=$fact->getPrice();
                    $totalPrice+=$fact->getPrice();
                    $tax+=$fact->getTax();
                    $totalTax+=$fact->getTax();
                }
      


                $dataPackagesFacturesDone["labels"]="Total paied";
                $dataPackagesFacturesDone["data"][]=$sum;
                $dataPackagesFacturesProcessing["labels"]="Total gaigned";
                $dataPackagesFacturesProcessing["data"][]=$tax;
            }
          
            $deliveries  = $this->entityManager->getRepository(User::class)->findBybyRoleByAccount("DELIVERY", $this->user->getAccount()->getId());
            $dataPackagesDelivery=[];
            $TotalPackageDelivery=0;
            foreach ($deliveries as $key => $del) {
                $dataPackagesDelivery["labels"][]= $del->getName();
                $dataPackagesDelivery["data"][]= count($del->getDeliverypackages());
                $TotalPackageDelivery+=count($del->getDeliverypackages());
            }
   
       
            return $this->render('Admin/dashbord/index.html.twig', [
                'waiting' => $waiting,
                'approved' => $approved,
                'picked' => $picked,
                'delivered' => $delivered,
                'returned' => $returned,
                "PackageExpeditor"=>$dataPackagesExpeditor,
                "PackageDelivery"=>$dataPackagesDelivery,
                "factDone"=>$dataPackagesFacturesDone,
                "factProc"=>$dataPackagesFacturesProcessing,
                "debut"=>$debut,
                "fin"=>$fin,
                "TotalPrice"=>$totalPrice,
                "TotalTax"=>$totalTax,
                "TotalPackageDelivery"=>$TotalPackageDelivery,
                "TotalPackageExpeditor"=>$TotalPackageExpeditor,

            ]);   
        }
        if (in_array('DELIVERY_ROLE',  $this->user->getRoles(), true)) {

       
            return $this->render('Delivery/dashbord/index.html.twig', [
                'controller_name' => 'DashbordController for Delivery',
            ]);   
        }

       
    }
    #[Route('/dashboard/filter', name: 'app_dashbord_filter')]
    public function filter( Request $req): JsonResponse
    {
         $this->user = $this->security->getUser();
   
        if(! $this->user){
            return $this->RedirectToRoute('app_login');    
        }
      
        if($req->request->get('debut') == null){
            $debut = date('Y-m-d', strtotime('-30 days'));
          
        }else{
            $debut = $req->request->get('debut');
        }
        if($req->request->get('fin') == null){
            $fin = date('Y-m-d');
        }else{
            $fin = $req->request->get('fin');

        }
     
        if (in_array('ADMIN_ROLE',  $this->user->getRoles(), true)) {


            $waiting = $this->entityManager->getRepository(Package::class)->getWithStateforAdmin("waiting", $this->user->getAccount()->getId(),$debut,$fin);
            $approved = $this->entityManager->getRepository(Package::class)->getWithStateforAdmin("approved", $this->user->getAccount()->getId(),$debut,$fin);
            $picked = $this->entityManager->getRepository(Package::class)->getWithStateforAdmin("picked", $this->user->getAccount()->getId(),$debut,$fin);
            $delivered = $this->entityManager->getRepository(Package::class)->getWithStateforAdmin("delivered", $this->user->getAccount()->getId(),$debut,$fin);
            $returned = $this->entityManager->getRepository(Package::class)->getWithStateforAdmin("returned", $this->user->getAccount()->getId(),$debut,$fin);
            
            $expeditors  = $this->entityManager->getRepository(User::class)->findBybyRoleByAccount("EXPEDITOR", $this->user->getAccount()->getId());
            $dataPackagesExpeditor=[];
            $dataPackagesFacturesDone=[];
            $dataPackagesFacturesProcessing=[];
            $totalPrice=0;
            $totalTax=0;
            $TotalPackageExpeditor=0;
            foreach ($expeditors as $key => $exp) {
                $dataPackagesExpeditor["labels"][]= $exp->getName();
                $pkgs = $this->entityManager->getRepository(Package::class)->findPackagesCountByExpeditorAndDateRange($exp->getId(),new \DateTime($debut) ,new \DateTime($fin));
                
                $dataPackagesExpeditor["data"][]= count($pkgs);
                $TotalPackageExpeditor+=count($pkgs);
                $factures = $this->entityManager->getRepository(Facture::class)->findFactureCountByExpeditorAndDateRange($exp->getId(),new \DateTime($debut) ,new \DateTime($fin));
                $sum = 0;
                $tax = 0;
                foreach ($factures as $key => $fact) {
                    $sum+=$fact->getPrice();
                    $totalPrice+=$fact->getPrice();
                    $tax+=$fact->getTax();
                    $totalTax+=$fact->getTax();
                }
      


                $dataPackagesFacturesDone["labels"]="Total paied";
                $dataPackagesFacturesDone["data"][]=$sum;
                $dataPackagesFacturesProcessing["labels"]="Total gaigned";
                $dataPackagesFacturesProcessing["data"][]=$tax;
            }
         
            $deliveries  = $this->entityManager->getRepository(User::class)->findBybyRoleByAccount("DELIVERY", $this->user->getAccount()->getId());
            $dataPackagesDelivery=[];
            $TotalPackageDelivery=0;
            foreach ($deliveries as $key => $del) {
                $pkgs = $this->entityManager->getRepository(Package::class)->findPackagesCountByDeliveryAndDateRange($del->getId(),new \DateTime($debut) ,new \DateTime($fin));

                $dataPackagesDelivery["labels"][]= $del->getName();
                $dataPackagesDelivery["data"][]= count($pkgs);
            $TotalPackageDelivery+= count($pkgs);

            }
            return new JsonResponse( [
                'waiting' => $waiting,
                'approved' => $approved,
                'picked' => $picked,
                'delivered' => $delivered,
                'returned' => $returned,
                "PackageExpeditor"=>$dataPackagesExpeditor,
                "PackageDelivery"=>$dataPackagesDelivery,
                "factDone"=>$dataPackagesFacturesDone,
                "factProc"=>$dataPackagesFacturesProcessing,
                "debut"=>$debut,
                "fin"=>$fin,
                "TotalPrice"=>$totalPrice,
                "TotalTax"=>$totalTax,
                "TotalPackageDelivery"=>$TotalPackageDelivery,
                "TotalPackageExpeditor"=>$TotalPackageExpeditor

            ]);   
        }
    }
     
}
