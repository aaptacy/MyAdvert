<?php

// src/AppBundle/Command/CreateUserCommand.php
namespace AppBundle\Command;

use AppBundle\Entity\Comment;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SendEmailCommand extends ContainerAwareCommand
{
protected function configure()
{
    $this->setName('send-email')
        ->setDescription('Sends an email informing about new comments concerning user advert')
        ->setHelp('This command allows you to send email to the user and informing about new comments');
}

protected function execute(InputInterface $input, OutputInterface $output)
{
    $em = $this->getContainer()->get('doctrine')->getEntityManager();
    $output->writeln([
        'Email has been send to',
    ]);


    $commentRepository = $this->getContainer()->get('doctrine')
        ->getRepository('AppBundle:Comment');
    $allComments = $commentRepository->findByNotify(false);
    $mailer = $this->getContainer()->get('mailer');
    $groupByUser = [];


    foreach ($allComments as $comment){
        if($comment->getAdvert() !== null){
            /** @var Comment $comment */
            $comment->setNotify(true);
            $em->persist($comment);
            $groupByUser[$comment->getAdvert()->getUser()->getId()]
            [$comment->getAdvert()->getId()][] = $comment;
    }
}


    $userRepository = $this->getContainer()->get('doctrine')
        ->getRepository('AppBundle:User');

    foreach($groupByUser as $userId => $adverts){
        $content = '';
        $user = $userRepository->findOneById($userId);
        foreach($adverts as $advertId => $comments) {
            $amountOfComments = count($comments);
            $content .= "Masz $amountOfComments komentarzy do ogłoszenia o id: $advertId <br>";
        }
            $message = (new \Swift_Message("New comments in your adverts!"))
                ->setFrom($this->getContainer()->getParameter('mailer_user'))
                ->setTo($user->getEmail())
                ->setBody($content, 'text/html');
            $mailer->send($message);
            $em->flush();
    }
}
}


