<?php

namespace Javanile\Imap2\Tests;

use Javanile\Imap2\Connection;
use PHPUnit\Framework\Error\Warning;

class CompatibilityTest extends ImapTestCase
{
    public function testOpenAndClose()
    {
        $username = 'wrong@username.local';
        $imap1 = @imap_open($this->mailbox, $username, $this->password);
        $error1 = @imap_errors();
        $imap2 = @imap2_open($this->mailbox, $username, $this->accessToken, OP_XOAUTH2);
        $error2 = @imap2_errors();

        $this->assertEquals($imap1, $imap2);

        $imap1 = imap_open($this->mailbox, $this->username, $this->password);
        $imap2 = imap2_open($this->mailbox, $this->username, $this->accessToken, OP_XOAUTH2);

        $this->assertTrue(is_resource($imap1));
        $this->assertInstanceOf(Connection::class, $imap2);

        $close1 = imap_close($imap1);
        $close2 = imap2_close($imap2);

        $this->assertEquals($close1, $close2);
    }

    public function testAppend()
    {
        $imap1 = imap_open($this->mailbox, $this->username, $this->password);
        $imap2 = imap2_open($this->mailbox, $this->username, $this->accessToken, OP_XOAUTH2);

        $check1 = imap_check($imap1);
        $check2 = imap2_check($imap2);
        $this->assertEquals($check1, $check2);

        $initialCount = $check2->Nmsgs;

        imap_append($imap1, $this->mailbox, $this->message);
        imap2_append($imap2, $this->mailbox, $this->message);

        $check1 = imap_check($imap1);
        $check2 = imap2_check($imap2);
        $this->assertEquals($check1, $check2);

        $finalCount = $check2->Nmsgs;

        imap_close($imap1);
        imap2_close($imap2);

        $this->assertEquals($initialCount + 2, $finalCount);
    }

    public function testList()
    {
        $imap1 = imap_open($this->mailbox, $this->username, $this->password);
        $imap2 = imap2_open($this->mailbox, $this->username, $this->accessToken, OP_XOAUTH2);

        $boxes1 = imap_list($imap1, $this->mailbox, '*');
        $boxes2 = imap2_list($imap2, $this->mailbox, '*');

        imap_close($imap1);
        imap2_close($imap2);

        $this->assertEquals($boxes1, $boxes2);
    }

    public function testCheck()
    {
        $imap1 = imap_open($this->mailbox, $this->username, $this->password);
        $imap2 = imap2_open($this->mailbox, $this->username, $this->accessToken, OP_XOAUTH2);

        $check1 = imap_check($imap1);
        $check2 = imap2_check($imap2);

        $check2->Date = $check1->Date;
        $check2->Mailbox = $check1->Mailbox;

        imap_close($imap1);
        imap2_close($imap2);

        $this->assertEquals($check1, $check2);
    }

    public function testCreateMailbox()
    {
        $imap1 = imap_open($this->mailbox, $this->username, $this->password);
        $imap2 = imap2_open($this->mailbox, $this->username, $this->accessToken, OP_XOAUTH2);

        $boxes1 = imap_list($imap1, $this->mailbox, '*');
        $boxes2 = imap2_list($imap2, $this->mailbox, '*');

        imap_close($imap1);
        imap2_close($imap2);

        $this->assertEquals($boxes1, $boxes2);
    }

    public function testFetchBody()
    {
        $imap1 = imap_open($this->mailbox, $this->username, $this->password);
        $imap2 = imap2_open($this->mailbox, $this->username, $this->accessToken, OP_XOAUTH2);

        for ($message = 1; $message < 10; $message++) {
            foreach ([null, 1] as $section) {
                $body1 = imap_fetchbody($imap1, $message, $section);
                file_put_contents('t1.txt', $body1);
                $body2 = imap2_fetchbody($imap2, $message, $section);
                file_put_contents('t2.txt', $body2);
                $this->assertEquals($body1, $body2);
            }
        }

        imap_close($imap1);
        imap2_close($imap2);
    }

    public function testDelete()
    {
        $imap1 = imap_open($this->mailbox, $this->username, $this->password);
        $imap2 = imap2_open($this->mailbox, $this->username, $this->accessToken, OP_XOAUTH2);

        $messages = '4:6';

        imap_expunge($imap1);
        $this->assertFalse(imap_search($imap1, 'DELETED'));
        $this->assertTrue(imap_delete($imap1, $messages));
        $deletedMessages1 = imap_search($imap1, 'DELETED');
        $deletedMessagesUid1 = imap_search($imap1, 'DELETED', SE_UID);
        $this->assertTrue(imap_undelete($imap1, $messages));
        $this->assertFalse(imap_search($imap1, 'DELETED'));

        $this->assertFalse(imap2_search($imap1, 'DELETED'));
        $this->assertTrue(imap2_delete($imap1, $messages));
        $deletedMessages2 = imap2_search($imap1, 'DELETED');
        $deletedMessagesUid2 = imap2_search($imap1, 'DELETED', SE_UID);
        $this->assertTrue(imap2_undelete($imap1, $messages));
        $this->assertFalse(imap2_search($imap1, 'DELETED'));

        $this->assertEquals($deletedMessages1, $deletedMessages2);
        $this->assertEquals($deletedMessagesUid1, $deletedMessagesUid2);

        imap_close($imap1);
        imap2_close($imap2);
    }
}
