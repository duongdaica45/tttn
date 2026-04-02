<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class GuiBangLuongTatCaMail extends Mailable
{
    use Queueable, SerializesModels;

    public $file;

    public function __construct($file)
    {
        $this->file = $file;
    }

    public function build()
    {
        return $this->subject("Bảng lương toàn bộ nhân viên")
            ->markdown('emails.bangluong_tatca')
            ->attach(storage_path('app/' . $this->file));
    }
}
