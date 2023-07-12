<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\SecurityBundle\Security;
use Doctrine\Persistence\ManagerRegistry;
use App\Entity\Package;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\UX\Chartjs\Model\Chart;
class DashbordController extends AbstractController
{
    private $security;
    private $entityManager;
    private $chart;
    public function __construct(Security $security,ManagerRegistry $doctrine,ChartBuilderInterface $chartBuilder)
    {
        $this->entityManager = $doctrine->getManager();
        $this->security = $security;
        $this->chart = $chartBuilder;
    }
    #[Route('/', name: 'app_dashbord')]
    public function index( $debut =null, $fin=null ): Response
    {
        if($debut == null){
            $debut = date('Y-m-d', strtotime('-30 days'));
        }
        if($fin == null){
            $fin = date('Y-m-d');
        }
  
        $user = $this->security->getUser();
   
        if(!$user){
            return $this->RedirectToRoute('app_login');    
        }
   
        if (in_array('EXPEDITOR_ROLE', $user->getRoles(), true)) {

           $waiting = $this->entityManager->getRepository(Package::class)->getWithState("waiting",$user->getId(),$debut,$fin);
           $approved = $this->entityManager->getRepository(Package::class)->getWithState("approved",$user->getId(),$debut,$fin);
           $picked = $this->entityManager->getRepository(Package::class)->getWithState("picked",$user->getId(),$debut,$fin);
           $delivered = $this->entityManager->getRepository(Package::class)->getWithState("delivered",$user->getId(),$debut,$fin);
           $returned = $this->entityManager->getRepository(Package::class)->getWithState("returned",$user->getId(),$debut,$fin);

            return $this->render('Expeditor/dashbord/index.html.twig', [
                'waiting' => $waiting,
                'approved' => $approved,
                'picked' => $picked,
                'delivered' => $delivered,
                'returned' => $returned,
            ]);   
        }
        if (in_array('ADMIN_ROLE', $user->getRoles(), true)) {

       
            return $this->render('Admin/dashbord/index.html.twig', [
                'controller_name' => 'DashbordController for Admin',
            ]);   
        }
        if (in_array('DELIVERY_ROLE', $user->getRoles(), true)) {

       
            return $this->render('Delivery/dashbord/index.html.twig', [
                'controller_name' => 'DashbordController for Delivery',
            ]);   
        }

       
    }
     
}
