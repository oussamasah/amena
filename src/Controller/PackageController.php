<?php

namespace App\Controller;

use App\Entity\Notification;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\SecurityBundle\Security;
use Doctrine\Persistence\ManagerRegistry;
use App\Entity\Package;
use App\Entity\User;
use App\Form\PackageType;
use App\Form\PackageValidateionType;

use Symfony\Component\Mercure\HubInterface;
use Dompdf\Dompdf;
use Endroid\QrCode\Color\Color;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelLow;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Label\Label;
use Endroid\QrCode\Logo\Logo;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Label\Font\NotoSans;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class PackageController extends AbstractController
{
    private $security;
    private $entityManager;
    private $user;

    public function __construct(Security $security, ManagerRegistry $doctrine)
    {

        // Avoid calling getUser() in the constructor: auth may not
        // be complete yet. Instead, store the entire Security object.
        $this->security = $security;
        $this->entityManager = $doctrine->getManager();
        $this->user= $this->security->getUser();


    }
    #[Route('/package', name: 'app_package')]
    public function index(): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED');
        $user = $this->security->getUser();


        if (in_array('EXPEDITOR_ROLE', $user->getRoles(), true)) {


            $packages = $user->getPackages();

            return $this->render('Expeditor/package/index.html.twig', [
                'packages' => $packages->toArray(),
            ]);
        }
        if (in_array('ADMIN_ROLE', $user->getRoles(), true)) {
            $packages = $this->entityManager->getRepository(Package::class)->findAllForAdmin($user->getAccount()->getId());
            return $this->render('Admin/package/index.html.twig', [
                'packages' => $packages,
            ]);
        }

        if (in_array('DELIVERY_ROLE', $user->getRoles(), true)) {

            $packages = $this->entityManager->getRepository(Package::class)->findAllForDelivery($user->getId());
            return $this->render('Delivery/package/index.html.twig', [
                'packages' => $packages,
            ]);
        }
    }

    #[Route('/package/add', name: 'app_add_package')]
    public function add(Request $req): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED');
        $user = $this->security->getUser();
        $pack = new Package();

        $form = $this->createForm(PackageType::class, $pack);
        $form->handlerequest($req);
        if ($form->isSubmitted() && $form->isValid()) {
            $pack->setState("waiting");
            $date = new \DateTime('@' . strtotime('now'));

            $pack->setCreateDate($date);
            $pack->setExpeditor($user);
           
            $this->entityManager->persist($pack);
            $this->entityManager->flush();
            return $this->redirectToRoute('app_package', array('msg' => "Package added successfuly"));
        }
        return $this->render('expeditor/package/add.html.twig', [
            'form' => $form->createView(),
        ]);
    }


    #[Route('/package/validate/{id}', name: 'app_validate_package')]
    public function validate(Request $req, $id, HubInterface $hub): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED');
        $pack = $this->entityManager->getRepository(Package::class)->find($id);
        if ($pack) {
            $pack->setState("approved");
            $pack->setValidatedAt(new \DateTime('@' . strtotime('now')));
            $this->entityManager->persist($pack);
            $this->entityManager->flush();
            $user = $this->security->getUser();
            $admin = $this->entityManager->getRepository(User::class)->findByRoleByAccount("ADMIN_ROLE",$user->getAccount()->getId());
            $notif  = new Notification();
            $notif->setTitle("A new package was approved");
            $notif->setMessage("The expeditor ".$pack->getExpeditor()->getName()." approved this package ".$pack->getLabel()." if you want to affect a delivery to this package <a href='/package/delivery/".$pack->getId()."'>click here</a>");
            $notif->setUser($admin);
            $notif->setIcon('<i class="fa-solid fa-box-circle-check text-danger"></i>');
            $notif->setCreatedAt(new \DateTime('@' . strtotime('now')));
            $notif->setSeen(0);
            $this->entityManager->persist($notif);
            $this->entityManager->flush();
            return $this->redirectToRoute("app_package", ["msg" => "Your package approved successfuly"]);
        } else {
            return $this->redirectToRoute("app_package", ["error" => "The package is not disponible"]);
        }
    }
    #[Route('/package/picked/{id}', name: 'app_picked_package')]
    public function picked(Request $req, $id): Response
    {
        
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED');
        $pack = $this->entityManager->getRepository(Package::class)->find($id);
        if ($pack) {
            $pack->setState("picked");
            $this->entityManager->persist($pack);
            $this->entityManager->flush();
            return $this->redirectToRoute("app_package", ["msg" => "Your package approved successfuly"]);
        } else {
            return $this->redirectToRoute("app_package", ["error" => "The package is not disponible"]);
        }
    }

    #[Route('/package/edit/{id}', name: 'app_edit_package')]
    public function edit(Request $req, $id): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED');
        $pack = $this->entityManager->getRepository(Package::class)->find($id);

        $form = $this->createForm(PackageType::class, $pack);
        $form->handlerequest($req);
        if ($form->isSubmitted() && $form->isValid()) {
            $p = new Package();
            $p = $form->getData();
            $this->entityManager->getRepository(Package::class)->save($p, true);

            return $this->redirectToRoute('app_package', array('msg' => "Package edited successfuly"));
        }
        return $this->render('expeditor/package/edit.html.twig', [
            'form' => $form->createView(),
        ]);
    }
    #[Route('/package/delivery/{id}', name: 'app_delivery_package')]
    public function delivery(Request $req, $id): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED');
        $pack = $this->entityManager->getRepository(Package::class)->find($id);
        $delivery = $this->entityManager->getRepository(User::class)->findByRole("DELIVERY_ROLE");

        $form = $this->createForm(PackageType::class, $pack);
        $form->handlerequest($req);
        if ($form->isSubmitted() && $form->isValid()) {
            $p = new Package();
            $p = $form->getData();

            $this->entityManager->getRepository(Package::class)->save($p, true);

            $notif  = new Notification();
            $notif->setTitle("A new package was affected to you");
            $notif->setMessage("You have new package to pick-up, if you want to see the pdf <a href='/package/pdf/".$pack->getId()."'>click here</a>");
            $notif->setUser($pack->getDelivery());
            $notif->setIcon('<i class="fa-solid fa-truck-clock  text-warning"></i>');
            $notif->setCreatedAt(new \DateTime('@' . strtotime('now')));
            $notif->setSeen(0);
            $this->entityManager->persist($notif);
            $this->entityManager->flush();
            return $this->redirectToRoute('app_package', array('msg' => "Package edited successfuly"));
        }
        return $this->render('admin/package/edit.html.twig', [
            'form' => $form->createView(),
            "delivery" => $delivery
        ]);
    }
    #[Route('/package/pdf/{id}', name: 'app_pdf_package')]
    public function pdf(Request $req, $id): Response
    {


        $p =  $this->entityManager->getRepository(Package::class)->find($id);

        $writer = new PngWriter();
        $qrCode = QrCode::create($_SERVER["SERVER_NAME"] . "/package/scan/" . $p->getId())
            ->setEncoding(new Encoding('UTF-8'))
            ->setErrorCorrectionLevel(new ErrorCorrectionLevelLow())
            ->setSize(120)
            ->setMargin(0)
            ->setForegroundColor(new Color(0, 0, 0))
            ->setBackgroundColor(new Color(255, 255, 255));
        $qrcode =  $writer->write($qrCode)->getDataUri();

        $photo = ($p->getExpeditor()->getPhoto() != null) ? 'upload/img/' . $p->getExpeditor()->getPhoto() : "img/profile.png";

        $data = [
            'profile'  => $this->imageToBase64($this->getParameter('kernel.project_dir') . '/public/' . $photo),
            'logo'  => $this->imageToBase64($this->getParameter('kernel.project_dir') . '/public/img/logoamena.png'),
            'qrcode'  => $qrcode,
            'package' => $p
        ];
        //   return  $this->render('pdf/package.html.twig',$data);
        $html = $this->renderView('pdf/package.html.twig', $data);


        $dompdf = new Dompdf();

        $dompdf->load_html($html);

        $dompdf->render();
        $dompdf->stream("package_informations.pdf");

        return new Response('', 200, [
            'Content-Type' => 'application/pdf',
        ]);
    }
    #[Route('/package/delete/{id}', name: 'app_delete_package')]
    public function delete(Request $req, $id): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED');
        $pack = $this->entityManager->getRepository(Package::class)->find($id);
        if ($pack) {
            $this->entityManager->remove($pack);
            $this->entityManager->flush();
            return $this->redirectToRoute("app_package", ["msg" => "Your package deleted successfuly"]);
        } else {
            return $this->redirectToRoute("app_package", ["error" => "The package is not disponible"]);
        }
    }



    #[Route('/package/scan/{id}', name: 'app_scan_package')]
    public function scan(Request $req, $id, UserPasswordHasherInterface $passwordHasher): Response
    {

        $pack = $this->entityManager->getRepository(Package::class)->find($id);

        if($pack && ($pack->getState() == "delivered" || $pack->getState() == "returned")){
            return $this->redirectToRoute("app_package");

        }

        $admin = $this->entityManager->getRepository(User::class)->findByRoleByAccount("ADMIN_ROLE", $pack->getExpeditor()->getAccount()->getId());

        $user = new User();
        $title = "Package state is " . $pack->getState();
        $form = $this->createForm(PackageValidateionType::class, $user);
        $upass = $pack->getDelivery();
        if ($pack->getState() == "approved" && $pack->getDelivery() != null) {
            $title .= " do you want to change it to pick-up?";
            $form->handlerequest($req);
            if ($form->isSubmitted() && $form->isValid()) {

           



                $hashedPassword = $passwordHasher->isPasswordValid(
                    $upass,
                    $user->getPassword()
                );

                if ($hashedPassword) {
                    $pack->setState("picked");
                    $pack->setPickedAt(new \DateTime('@' . strtotime('now')));
                    $this->entityManager->persist($pack);
                    $this->entityManager->flush();

                    return $this->render('expeditor/package/scan.html.twig', [
                        "id" => $id,
                        'form' => $form->createView(),
                        'package' => $pack,
                        "title" => " do you want to change it to pending?",
                        'msg' => "Package picked successfuly"
                    ]);
                } else {
                    return $this->render('expeditor/package/scan.html.twig', [
                        "id" => $id,
                        'form' => $form->createView(),
                        'package' => $pack,
                        "title" => $title,
                        'error' => "Bad Credential"
                    ]);
                }
            }
            return $this->render('expeditor/package/scan.html.twig', [
                "id" => $id,
                'package' => $pack,
                'form' => $form->createView(),
                "title" => $title,

            ]);
        } else if ($pack->getState() == "picked") {
            $title .= " do you want to change it to pending?";
            $form->handlerequest($req);
            if ($form->isSubmitted() && $form->isValid()) {





                $hashedPassword = $passwordHasher->isPasswordValid(
                    $admin,
                    $user->getPassword()
                );

                if ($hashedPassword) {
                    $pack->setState("pending");
                    $pack->setPendingAt(new \DateTime('@' . strtotime('now')));
                    $this->entityManager->persist($pack);
                    $this->entityManager->flush();
                    return $this->render('expeditor/package/scan.html.twig', [
                        "id" => $id,
                        'form' => $form->createView(),
                        'package' => $pack,
                        "title" =>  " do you want to change it to proccessing?",
                        'msg' => "Package pending successfuly"

                    ]);
                } else {

                    return $this->render('expeditor/package/scan.html.twig', [
                        "id" => $id,
                        'form' => $form->createView(),
                        'package' => $pack,
                        "title" =>  " do you want to change it to proccessing?",
                        'error' => "Bad Credential"
                    ]);
                }

                return $this->render('expeditor/package/scan.html.twig', [
                    "id" => $id,
                    'form' => $form->createView(),
                    'package' => $pack,
                    "title" => $title,

                ]);
            }
        } else if ($pack->getState() == "pending" && $pack->getDelivery() != null) {
            $title .= " do you want to change it to proccessing?";
            $form->handlerequest($req);
            if ($form->isSubmitted() && $form->isValid()) {





                $hashedPassword = $passwordHasher->isPasswordValid(
                    $admin,
                    $user->getPassword()
                );

                if ($hashedPassword) {
                    $pack->setState("processing");
                    $pack->setProcessingAt(new \DateTime('@' . strtotime('now')));
                    $this->entityManager->persist($pack);
                    $this->entityManager->flush();
                    return $this->render('expeditor/package/scan.html.twig', [
                        "id" => $id,
                        'form' => $form->createView(),
                        'package' => $pack,
                        "title" => " do you want to change it to delivered or returned?",
                        'msg' => "Package proccessing successfuly"

                    ]);
                } else {

                    return $this->render('expeditor/package/scan.html.twig', [
                        "id" => $id,
                        'form' => $form->createView(),
                        'package' => $pack,
                        "title" => $title,
                        'error' => "Bad Credential"
                    ]);
                }

                return $this->render('expeditor/package/scan.html.twig', [
                    "id" => $id,
                    'form' => $form->createView(),
                    'package' => $pack,
                    "title" => $title,

                ]);
            }
        }  else if ($pack->getState() == "processing" && $pack->getDelivery() != null) {
            $title .= " do you want to change it to delivered or returned?";
            $form->handlerequest($req);
            if ($form->isSubmitted() && $form->isValid()) {




                $hashedPasswordadmin = $passwordHasher->isPasswordValid(
                    $admin,
                    $user->getPassword()
                );
                $hashedPassworddelivery = $passwordHasher->isPasswordValid(
                    $upass,
                    $user->getPassword()
                );
                if ($hashedPassworddelivery) {
                    $pack->setState("delivered");
                    $pack->setClosedAt(new \DateTime('@' . strtotime('now')));
                    $this->entityManager->persist($pack);
                    $this->entityManager->flush();

                    $notif  = new Notification();
                    $notif->setTitle("A package was delivred");
                    $notif->setMessage("The package ".$pack->getLabel()." delivred delivred to client if you want to see the pdf  <a href='/package/pdf/".$pack->getId()."'>click here</a>");
                    $notif->setUser($pack->getExpeditor());
                    $notif->setIcon('<i class="fa-solid fa-hand-holding-box text-success"></i>');
                    $notif->setCreatedAt(new \DateTime('@' . strtotime('now')));
                    $notif->setSeen(0);
                    $this->entityManager->persist($notif);
                    $this->entityManager->flush();


                    return $this->redirectToRoute("app_package");
                }else if ($hashedPasswordadmin) {
                    $pack->setState("returned");
                    $pack->setClosedAt(new \DateTime('@' . strtotime('now')));
                    $this->entityManager->persist($pack);
                    $this->entityManager->flush();

                    $notif  = new Notification();
                    $notif->setTitle("A package was returned");
                    $notif->setMessage("The package ".$pack->getLabel()." returned we are sorry we can't contact the client, if you want to see the pdf  <a href='/package/pdf/".$pack->getId()."'>click here</a>");
                    $notif->setUser($pack->getExpeditor());
                    $notif->setIcon('<i class="fa-solid fa-truck-fast text-danger"></i>');
                    $notif->setCreatedAt(new \DateTime('@' . strtotime('now')));
                    $notif->setSeen(0);
                    $this->entityManager->persist($notif);
                    $this->entityManager->flush();

                    return $this->redirectToRoute("app_package");
                } else {

                    return $this->render('expeditor/package/scan.html.twig', [
                        "id" => $id,
                        'form' => $form->createView(),
                        'package' => $pack,
                        "title" => $title,
                        'error' => "Bad Credential"
                    ]);
                }

                return $this->render('expeditor/package/scan.html.twig', [
                    "id" => $id,
                    'form' => $form->createView(),
                    'package' => $pack,
                    "title" => $title,

                ]);
            }
        } else {
            return $this->redirectToRoute("app_package");
        }


        return $this->render('expeditor/package/scan.html.twig', [
            "id" => $id,
            'form' => $form->createView(),
            'package' => $pack,
            "title" => $title,

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
