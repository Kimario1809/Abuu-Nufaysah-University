<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Models\Chat;
use App\Models\Message;
use App\Models\MessageFile;
use App\Models\Theme;
use App\Models\User;

class ChatController extends Controller {
    protected $auth;

    public function __construct() {
        parent::__construct();
        $this->auth = Auth::getInstance();
        $this->requireAuth();
    }

    public function index() {
        $user = $this->auth->getCurrentUser();
        $contacts = User::findAll([], 'full_name ASC');
        $chats = Chat::getForUser($user['id']);
        $theme = Theme::getByUserId($user['id']);
        $presetThemes = Theme::getPresetThemes();

        $filteredContacts = [];
        foreach ($contacts as $contact) {
            if ((int)$contact['id'] !== (int)$user['id']) {
                $filteredContacts[] = $contact;
            }
        }

        $data = [
            'contacts' => $filteredContacts,
            'chats' => $chats,
            'theme' => $theme,
            'presetThemes' => $presetThemes,
            'activePage' => 'chat'
        ];

        $this->view('chat/index', $data);
    }

    public function conversations($contactId) {
        $user = $this->auth->getCurrentUser();
        $chat = Chat::findOrCreate($user['id'], $contactId);
        $messages = Message::getByChat($chat['id']);
        $contact = User::find($contactId);

        $this->json([
            'chat' => $chat,
            'contact' => $contact,
            'messages' => $messages
        ]);
    }

    public function sendMessage() {
        $user = $this->auth->getCurrentUser();
        $chatId = $_POST['chat_id'] ?? null;
        $contactId = $_POST['contact_id'] ?? null;
        $body = trim($_POST['body'] ?? '');

        if (!$chatId && !$contactId) {
            $this->json(['success' => false, 'message' => 'Missing chat target'], 400);
            return;
        }

        $chat = $chatId ? Chat::find($chatId) : Chat::findOrCreate($user['id'], $contactId);
        $message = Message::createMessage([
            'chat_id' => $chat['id'],
            'sender_id' => $user['id'],
            'body' => $body,
            'status' => 'sent'
        ]);

        $this->json(['success' => true, 'message' => $message]);
    }

    public function uploadFile() {
        $user = $this->auth->getCurrentUser();
        if (empty($_FILES['file']['name'])) {
            $this->json(['success' => false, 'message' => 'No file uploaded'], 400);
            return;
        }

        $allowed = ['jpg', 'jpeg', 'png', 'webp', 'mp4', 'mov', 'pdf', 'docx', 'xlsx', 'zip'];
        $ext = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed, true)) {
            $this->json(['success' => false, 'message' => 'File type not allowed'], 400);
            return;
        }

        $targetDir = ROOT_PATH . '/storage/uploads/chat';
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0777, true);
        }

        $fileName = uniqid() . '_' . basename($_FILES['file']['name']);
        $targetPath = $targetDir . '/' . $fileName;
        move_uploaded_file($_FILES['file']['tmp_name'], $targetPath);

        $chat = Chat::findOrCreate($user['id'], $_POST['contact_id'] ?? 0);
        $message = Message::createMessage([
            'chat_id' => $chat['id'],
            'sender_id' => $user['id'],
            'body' => $_POST['body'] ?? '',
            'message_type' => 'file',
            'status' => 'sent'
        ]);

        MessageFile::create([
            'message_id' => $message['id'],
            'file_name' => $fileName,
            'file_path' => '/storage/uploads/chat/' . $fileName,
            'file_type' => $ext,
            'stored_at' => date('Y-m-d H:i:s')
        ]);

        $this->json(['success' => true, 'message' => $message]);
    }

    public function theme() {
        $user = $this->auth->getCurrentUser();
        $themeName = $_POST['theme_name'] ?? 'Default Blue Theme';
        $customColors = $_POST['custom_colors'] ?? [];
        Theme::saveForUser($user['id'], $themeName, $customColors);
        $this->json(['success' => true, 'theme' => $themeName]);
    }

    public function status() {
        $user = $this->auth->getCurrentUser();
        $this->json(['success' => true, 'online' => true, 'user' => $user['id']]);
    }
}
