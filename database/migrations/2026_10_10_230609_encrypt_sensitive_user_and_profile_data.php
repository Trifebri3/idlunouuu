<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Update users table columns
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['email']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->text('email')->change();
            $table->text('name')->change();
            $table->string('email_bidx', 64)->nullable()->unique()->after('email');
        });

        // 2. Update profiles table columns
        Schema::table('profiles', function (Blueprint $table) {
            $table->text('display_name')->change();
        });

        // 3. Update nfc_orders table columns
        Schema::table('nfc_orders', function (Blueprint $table) {
            $table->text('card_printed_name')->change();
            $table->text('phone_or_whatsapp')->change();
        });

        // 4. Encrypt existing users data
        $users = DB::table('users')->get();
        foreach ($users as $user) {
            $isEmailEnc = str_starts_with($user->email ?? '', 'eyJpdiI');
            $isNameEnc = str_starts_with($user->name ?? '', 'eyJpdiI');

            $plainEmail = $isEmailEnc ? Crypt::decryptString($user->email) : $user->email;
            $encEmail = $isEmailEnc ? $user->email : Crypt::encryptString($user->email);
            $encName = $isNameEnc ? $user->name : Crypt::encryptString($user->name);
            $emailBidx = hash_hmac('sha256', strtolower(trim($plainEmail)), (string) config('app.key'));

            DB::table('users')->where('id', $user->id)->update([
                'name' => $encName,
                'email' => $encEmail,
                'email_bidx' => $emailBidx,
            ]);
        }

        // 5. Encrypt existing profiles data
        $profiles = DB::table('profiles')->get();
        foreach ($profiles as $profile) {
            $updates = [];
            if ($profile->display_name && ! str_starts_with($profile->display_name, 'eyJpdiI')) {
                $updates['display_name'] = Crypt::encryptString($profile->display_name);
            }
            if ($profile->bio && ! str_starts_with($profile->bio, 'eyJpdiI')) {
                $updates['bio'] = Crypt::encryptString($profile->bio);
            }
            if (! empty($updates)) {
                DB::table('profiles')->where('id', $profile->id)->update($updates);
            }
        }

        // 6. Encrypt existing nfc_orders data
        $orders = DB::table('nfc_orders')->get();
        foreach ($orders as $order) {
            $updates = [];
            if ($order->card_printed_name && ! str_starts_with($order->card_printed_name, 'eyJpdiI')) {
                $updates['card_printed_name'] = Crypt::encryptString($order->card_printed_name);
            }
            if ($order->phone_or_whatsapp && ! str_starts_with($order->phone_or_whatsapp, 'eyJpdiI')) {
                $updates['phone_or_whatsapp'] = Crypt::encryptString($order->phone_or_whatsapp);
            }
            if ($order->shipping_address && ! str_starts_with($order->shipping_address, 'eyJpdiI')) {
                $updates['shipping_address'] = Crypt::encryptString($order->shipping_address);
            }
            if ($order->notes && ! str_starts_with($order->notes, 'eyJpdiI')) {
                $updates['notes'] = Crypt::encryptString($order->notes);
            }
            if (! empty($updates)) {
                DB::table('nfc_orders')->where('id', $order->id)->update($updates);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Decrypt users
        $users = DB::table('users')->get();
        foreach ($users as $user) {
            try {
                $plainEmail = str_starts_with($user->email ?? '', 'eyJpdiI') ? Crypt::decryptString($user->email) : $user->email;
                $plainName = str_starts_with($user->name ?? '', 'eyJpdiI') ? Crypt::decryptString($user->name) : $user->name;
                DB::table('users')->where('id', $user->id)->update([
                    'email' => $plainEmail,
                    'name' => $plainName,
                ]);
            } catch (Throwable $e) {
                // Ignore if already decrypted
            }
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('email_bidx');
            $table->string('email')->change();
            $table->string('name')->change();
            $table->unique('email');
        });

        // Decrypt profiles
        $profiles = DB::table('profiles')->get();
        foreach ($profiles as $profile) {
            try {
                $plainDisplay = str_starts_with($profile->display_name ?? '', 'eyJpdiI') ? Crypt::decryptString($profile->display_name) : $profile->display_name;
                $plainBio = str_starts_with($profile->bio ?? '', 'eyJpdiI') ? Crypt::decryptString($profile->bio) : $profile->bio;
                DB::table('profiles')->where('id', $profile->id)->update([
                    'display_name' => $plainDisplay,
                    'bio' => $plainBio,
                ]);
            } catch (Throwable $e) {
            }
        }

        Schema::table('profiles', function (Blueprint $table) {
            $table->string('display_name')->change();
        });

        // Decrypt nfc_orders
        $orders = DB::table('nfc_orders')->get();
        foreach ($orders as $order) {
            try {
                $plainCard = str_starts_with($order->card_printed_name ?? '', 'eyJpdiI') ? Crypt::decryptString($order->card_printed_name) : $order->card_printed_name;
                $plainPhone = str_starts_with($order->phone_or_whatsapp ?? '', 'eyJpdiI') ? Crypt::decryptString($order->phone_or_whatsapp) : $order->phone_or_whatsapp;
                $plainAddress = str_starts_with($order->shipping_address ?? '', 'eyJpdiI') ? Crypt::decryptString($order->shipping_address) : $order->shipping_address;
                $plainNotes = str_starts_with($order->notes ?? '', 'eyJpdiI') ? Crypt::decryptString($order->notes) : $order->notes;
                DB::table('nfc_orders')->where('id', $order->id)->update([
                    'card_printed_name' => $plainCard,
                    'phone_or_whatsapp' => $plainPhone,
                    'shipping_address' => $plainAddress,
                    'notes' => $plainNotes,
                ]);
            } catch (Throwable $e) {
            }
        }

        Schema::table('nfc_orders', function (Blueprint $table) {
            $table->string('card_printed_name', 100)->change();
            $table->string('phone_or_whatsapp', 50)->change();
        });
    }
};
