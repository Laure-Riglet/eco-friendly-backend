<?php

namespace App\Controller;

use App\Entity\Advice;
use App\Form\AdviceType;
use App\Form\ContentListType;
use App\Repository\AdviceRepository;
use App\Service\SluggerService;
use DateTime;
use DateTimeImmutable;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;


class AdviceController extends AbstractController
{
    /**
     * @Route("/back_office/conseils", name="app_backoffice_advices_list", methods={"GET", "POST"})
     * @isGranted("ROLE_ADMIN"), message="Vous n'avez pas les droits pour accéder à cette page"
     */
    public function list(Request $request, AdviceRepository $adviceRepository): Response
    {
        $advices = $adviceRepository->findAllOrderByDate();

        $form = $this->createForm(ContentListType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $advices = $adviceRepository->findAllWithFilter(
                $form->get('sortType')->getData() ?? 'created_at',
                $form->get('sortOrder')->getData() ?? 'DESC',
                $form->get('title')->getData(),
                $form->get('content')->getData(),
                $form->get('status')->getData(),
                $form->get('user')->getData(),
                $form->get('category')->getData(),
                DateTimeImmutable::createFromMutable($form->get('dateFrom')->getData() ?? new DateTime('2000-01-01')),
                DateTimeImmutable::createFromMutable($form->get('dateTo')->getData() ?? new DateTime('now'))
            );

            return $this->render('advice/list.html.twig', [
                'advices' => $advices,
                'form' => $form->createView()
            ]);
        }

        return $this->renderForm('advice/list.html.twig', [
            'advices' => $advices,
            'form' => $form
        ]);
    }

    /**
     * @Route("/back_office/conseils/{id}", name="app_backoffice_advices_show", requirements={"id":"\d+"}, methods={"GET"})
     * @isGranted("ROLE_ADMIN"), message="Vous n'avez pas les droits pour accéder à cette page"
     */
    public function show(Advice $advice): Response
    {
        return $this->render('advice/show.html.twig', [
            'advice' => $advice,
        ]);
    }

    /**
     * @Route("/back_office/conseils/{id}/editer", name="app_backoffice_advices_edit", requirements={"id":"\d+"}, methods={"GET", "POST"})
     * @isGranted("ROLE_ADMIN"), message="Vous n'avez pas les droits pour accéder à cette page"
     */
    public function edit(Request $request, SluggerService $slugger, Advice $advice, AdviceRepository $adviceRepository): Response
    {
        $form = $this->createForm(AdviceType::class, $advice);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $advice->setSlug($slugger->slugify($advice->getTitle()));
            $advice->setUpdatedAt(new DateTimeImmutable());
            $adviceRepository->add($advice, true);

            $this->addFlash(
                'success',
                '"' . $advice->getTitle() . '" a bien été modifié.'
            );

            return $this->redirectToRoute('app_backoffice_advices_list', [], Response::HTTP_SEE_OTHER);
        }


        return $this->renderForm('advice/edit.html.twig', [
            'advice' => $advice,
            'form' => $form,
        ]);
    }

    /**
     * @Route("/back_office/conseils/{id}/desactiver", name="app_backoffice_advices_deactivate", requirements={"id":"\d+"}, methods={"POST"})
     * @isGranted("ROLE_ADMIN"), message="Vous n'avez pas les droits pour accéder à cette page"
     */
    public function deactivate(Advice $advice, AdviceRepository $adviceRepository, Request $request): Response
    {
        if ($this->isCsrfTokenValid('deactivate' . $advice->getId(), $request->request->get('_token'))) {
            $advice->setStatus(2);
            $advice->setUpdatedAt(new DateTimeImmutable());
            $adviceRepository->add($advice, true);
        }
        $this->addFlash(
            'danger',
            '"' . $advice->getTitle() . '" a bien été désactivé. '
        );
        return $this->redirectToRoute('app_backoffice_advices_list', [], Response::HTTP_SEE_OTHER);
    }

    /**
     * @Route("/back_office/conseils/{id}/reactiver", name="app_backoffice_advices_reactivate", requirements={"id":"\d+"}, methods={"POST"})
     * @isGranted("ROLE_ADMIN"), message="Vous n'avez pas les droits pour accéder à cette page"
     */
    public function reactivate(Advice $advice, AdviceRepository $adviceRepository, Request $request): Response
    {
        if ($this->isCsrfTokenValid('reactivate' . $advice->getId(), $request->request->get('_token'))) {
            $advice->setStatus(1);
            $advice->setUpdatedAt(new DateTimeImmutable());
            $adviceRepository->add($advice, true);
        }
        $this->addFlash(
            'success',
            '"' . $advice->getTitle() . '" a bien été réactivé. '
        );
        return $this->redirectToRoute('app_backoffice_advices_list', [], Response::HTTP_SEE_OTHER);
    }
}
