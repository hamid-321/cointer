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
use App\Service\Ai\PortfolioTool;

class ChatbotController extends AbstractController
{
    public function __construct(
        #[Autowire(service: 'ai.agent.default')]
        private readonly AgentInterface $agent,
        private readonly PortfolioTool $portfolioTool
    ) {
    }

    #[Route('/chatbot', name: 'app_chatbot', methods: ['POST'])]
    public function index(Request $request): JsonResponse
    {
        if (!$this->isCsrfTokenValid('chatbot', $request->headers->get('X-CSRF-Token'))) {
            return new JsonResponse(['response' => 'Invalid security token. Please refresh the page.'], 403);
        }

        $payload = $request->toArray();
        $userText = $payload['message'] ?? '';
        $history = $payload['history'] ?? [];

        $portfolioData = $this->portfolioTool->__invoke();
        $portfolioDataForPrompt = "User's current crypto portfolio data: " . json_encode($portfolioData);

        $messages = new MessageBag(Message::forSystem(
            "You are Cointer Assistant. $portfolioDataForPrompt. Use this info to answer questions directly."));

        foreach ($history as $message) {
            if ($message['role'] === 'user') 
            {
                $messages->add(Message::ofUser($message['content']));
            } 
            else
            {
                $messages->add(Message::ofAssistant($message['content']));
            }
        }

        $result = $this->agent->call($messages);

        return new JsonResponse(['response' => (string) $result->getContent()]);
    }
}