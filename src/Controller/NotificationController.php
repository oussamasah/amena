<?php

namespace App\Controller;

use App\Entity\Notification;
use Symfony\Bundle\SecurityBundle\Security;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;


class NotificationController extends AbstractController
{
    private $security;
    private $entityManager;

    public function __construct(Security $security, ManagerRegistry $doctrine)
    {
        $this->security = $security;
        $this->entityManager = $doctrine->getManager();

    }
    #[Route('/notification', name: 'app_notification')]
    public function index(): Response
    {
        
        $user = $this->security->getUser();
       $notifs = $this->entityManager->getRepository(Notification::class)->findAllByUser($user->getId());
       foreach ($notifs as $key => $notif) {
        $notif->createAt=$this->dateDifference($notif->getCreatedAt());
        # code...
       }
        return $this->render('notification/index.html.twig', [
            'notifications' => $notifs,
        ]);
    }
    #[Route('/notification/seen/{id}', name: 'app_notification_seen')]
    public function seen($id):Response
    {
        
        $user = $this->security->getUser();
       $notif = $this->entityManager->getRepository(Notification::class)->find($id);
       $notif->setSeen(1);
       $notif->setSeenAt(new \DateTime());
       $this->entityManager->persist($notif);
       $this->entityManager->flush();
    return true;
    }


    public function dateDifference($startDateString): String
    {


        // Convert strings to DateTime objects
        $startDate = $startDateString;
        $endDate = new \DateTime();

        // Calculate the difference between the two dates
        $interval = $startDate->diff($endDate);

        // Get the difference in days, hours, and minutes
        $days = $interval->days;
        $hours = $interval->h;
        $minutes = $interval->i;

        // Optionally, you can calculate the total difference in minutes or hours
        $totalMinutes = $days * 24 * 60 + $hours * 60 + $minutes;
        $totalHours = $days * 24 + $hours;

        $diff = "";
        if($days > 0){
            $diff.=$days." days ";
        };
        if($hours > 0 ){
            $diff.=$hours." hours ";
        };

        if($minutes > 0){
            $diff.=$minutes." minutes";
        };
return $diff;


    }
}
