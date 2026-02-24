<?php

namespace App\Controller;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\AI\Agent\AgentInterface;
use Symfony\AI\Platform\Message\Message;
use Symfony\AI\Platform\Message\MessageBag;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class ChatbotController extends AbstractController
{
    public function __construct(
        #[Autowire(service: 'ai.agent.default')]
        private readonly AgentInterface $agent
    ) {
    }

    #[Route('/chatbot', name: 'app_chatbot', methods: ['POST'])]
    public function index(Request $request): JsonResponse
    {
        $payload = $request->toArray();
        $userText = $payload['message'] ?? '';

        $messages = new MessageBag(Message::ofUser($userText));
        $result = $this->agent->call($messages);

        return new JsonResponse(['response' => (string) $result->getContent()]);
    }
}