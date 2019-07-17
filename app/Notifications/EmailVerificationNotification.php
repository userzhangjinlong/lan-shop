<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * ShouldQueue 这个接口本身没有定义任何方法，对于实现了 ShouldQueue 的邮件类 Laravel 会用将发邮件的操作放进队列里来实现异步发送；
 *
 * Class EmailVerificationNotification
 * @package App\Notifications
 */
class EmailVerificationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Get the notification's delivery channels.
     *
     * 只需要通过邮件通知，因此这里只需要一个 mail 即可 即这里是否可以衍生为需要短信发送的按钮操作
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     *greeting() 方法可以设置邮件的欢迎词；
     * subject() 方法用来设定邮件的标题；
     * line() 方法会在邮件内容里添加一行文字；
     * action() 方法会在邮件内容里添加一个链接按钮。这里就是激活链接
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        // 使用 Laravel 内置的 Str 类生成随机字符串的函数，参数就是要生成的字符串长度
        $token = Str::random(16);
        //往缓存中写入这个随机字符串，有效时间为 30 分钟。
        Cache::set('email_verification_'.$notifiable->email, $token, 30);
        $url = route('email_verification.verify', ['email' => $notifiable->email, 'token' => $token]);

        return (new MailMessage)
            ->greeting($notifiable->name.'您好：')
            ->subject('注册成功，请验证您的邮箱')
            ->line('请点击下方链接验证您的邮箱')
            ->action('验证', $url);

    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            //
        ];
    }
}
