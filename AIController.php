<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\AiChatModel;
use App\Services\OpenAIService;

class AIController extends Controller {
    public function chat() {
        $this->view('ai/chat', [], false);
    }

    public function ask() {
        $message = trim($_POST['message'] ?? '');
        if ($message === '') {
            $this->json(['success' => false, 'message' => 'Please enter a message.'], 400);
            return;
        }

        $userId = $this->auth->getCurrentUser()['id'] ?? 0;
        $role = $this->auth->getCurrentRole() ?? 'guest';
        AiChatModel::saveMessage($userId, $role, 'user', $message);

        $context = [
            'role' => $role,
            'user_id' => $userId
        ];

        $service = new OpenAIService();
        $result = $service->ask($message, $context);

        if (!$result['success']) {
            AiChatModel::saveMessage($userId, $role, 'assistant', $result['message']);
            $this->json(['success' => false, 'message' => $result['message']], 500);
            return;
        }

        AiChatModel::saveMessage($userId, $role, 'assistant', $result['answer']);
        $this->json(['success' => true, 'answer' => $result['answer']]);
    }

    public function history() {
        $userId = $this->auth->getCurrentUser()['id'] ?? 0;
        $this->json(AiChatModel::getHistory($userId));
    }
}
