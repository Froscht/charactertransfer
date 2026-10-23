<?php

class Charactertransfer extends MX_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->user->userArea();
        $this->load->model('transfer_model');
    }

    public function index()
    {
        requirePermission("view");

        $this->template->setTitle("Character Transfer");

        $user_id = $this->user->getId();

        $data = array(
            "user_id" => $user_id,
            "csrf_name" => $this->security->get_csrf_token_name(),
            "csrf_hash" => $this->security->get_csrf_hash()
        );

        if ($this->input->post()) {
            // Validate file upload
            if (!$this->validateFileUpload()) {
                $this->error("Invalid file upload. Please ensure the file is a valid .lua file under 5MB.");
                return;
            }

            // Get secure file content
            $chardump = $this->getSecureFileContent();
            if ($chardump === false) {
                $this->error("Failed to read file content or file is too large.");
                return;
            }

            // Check if the file data is empty
            if (!empty($chardump)) {
                // Prepare data for insert
                if (!$this->transfer_model->insertTransfer($chardump)) {
                    $this->error("Character has already been uploaded before. If you have a question, contact the Administrator.");
                }
            } else {
                $this->error("The uploaded file is empty.");
            }
        }

        $data["transferdata"] = $this->transfer_model->getTransfersByAccountID($user_id);

        $content = $this->template->loadPage("charactertransfer.tpl", $data);
        $page = $this->template->box('Character Transfer', $content);
        $this->template->view($page, false, false);
    }
    public function download()
    {
        $file = "application/modules/charactertransfer/chardump.zip";

        if (file_exists($file) && is_readable($file)) {
            $this->load->helper('download');
            force_download($file, null);
        } else {
            show_404("File not Found");
        }
    }



    public function error($msg)
    {
        // Sanitize error message
        $msg = htmlspecialchars($msg, ENT_QUOTES, 'UTF-8');
        $data = array('msg' => $msg);
        $page = $this->template->loadPage("error.tpl", $data);
        $this->template->box("error", $page, true);
    }
    /**
     * Validate file upload for security
     */
    private function validateFileUpload()
    {
        if (!isset($_FILES['fileToUpload']) || $_FILES['fileToUpload']['error'] !== UPLOAD_ERR_OK) {
            return false;
        }

        $file = $_FILES['fileToUpload'];

        // Check file size (max 5MB)
        if ($file['size'] > 5242880) {
            return false;
        }

        // Check MIME type
        $allowedMimeTypes = ['text/plain', 'application/octet-stream'];
        if (!in_array($file['type'], $allowedMimeTypes)) {
            return false;
        }

        // Check file extension
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if ($extension !== 'lua') {
            return false;
        }

        // Check for malicious content in filename
        if (preg_match('/[<>\"\']/', $file['name'])) {
            return false;
        }

        return true;
    }

    /**
     * Get file content with security measures
     */
    private function getSecureFileContent()
    {
        $file = $_FILES['fileToUpload']['tmp_name'];

        // Use CodeIgniter's security helper to clean the content
        $this->load->helper('security');
        $content = file_get_contents($file);

        // Basic sanitization - remove potential script tags
        $content = strip_tags($content);

        // Limit content size
        if (strlen($content) > 10485760) { // 10MB
            return false;
        }

        return $content;
    }
}