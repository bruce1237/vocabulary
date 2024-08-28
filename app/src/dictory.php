<?php

class dictory
{
    protected int $defaultProgressDaily = 35;

    protected string $fullList = "./src/fullList";
    protected string $completedList = "completedList";
    protected string $remainingList = "remainingList";
    protected string $workingInProgress = "workingInProgress";
    protected string $workingInProgress1 = "workingInProgress1";
    protected string $audioFolder = "./Audio/";
    protected string $phoneticsUrl = "https://api.dictionaryapi.dev/api/v2/entries/en/";
    protected string $thesaurusUrl = "https://www.dictionaryapi.com/api/v3/references/thesaurus/json/";
    protected string $dictionaryUrl = "https://www.dictionaryapi.com/api/v3/references/collegiate/json/";
    protected string $thesaurusKey = "?key=a41e49a8-fff0-4d55-93a9-114fcb7e5075";
    protected string $dictionaryKey = "?key=befa79b8-e956-4bbe-b69d-6801aec5e5ce";

    // https://www.dictionaryapi.com/api/v3/references/thesaurus/json/desecration?key=befa79b8-e956-4bbe-b69d-6801aec5e5ce
    // https://www.dictionaryapi.com/api/v3/references/collegiate/json/voluminous?key=befa79b8-e956-4bbe-b69d-6801aec5e5ce

    protected CurlHandle $curl;
    protected Redis $redis;

    public function __construct()
    {
        $this->curl = curl_init();
        curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, true); // 以字符串返回结果而不是直接输出
        curl_setopt($this->curl, CURLOPT_SSL_VERIFYPEER, false); // 禁用 SSL 证书验证（不推荐在生产环境中禁用）
        curl_setopt($this->curl, CURLOPT_HEADER, 0); // 不包括头部信息

        $this->redis = new Redis();
        $this->redis->connect("redis");
    }


    public function getWord(string $word): string
    {

        $word = trim($word);

        if ($response = $this->wordExist($word)) {
            return $response;
        }
        $phonetics = $this->getPhonetics($word);
        $thesaurus = $this->getThesaurus($word);
        $dictionary = $this->getDictionary($word);

        $def = [
            "word" => $word,
            "phonetics" => $phonetics,
            "syns" => $thesaurus['syns'],
            "ants" => $thesaurus['ants'],
            "dictionary" => $dictionary['definition'],
        ];

        $this->putCache($def);

        return json_encode($def);
    }

    public function getWordTest(string $word)
    {
        $word = json_decode($this->getWord($word), true);
        $fakeWords = $this->getFakeWords();
        $options = $this->getSynsOptions($word, $fakeWords);
        return json_encode($options);
    }

    protected function getSynsOptions($word, $fakeWords)
    {

        $syns = [];

        foreach ($fakeWords as $fakeWord) {
            $syns = array_merge($syns, $fakeWord['syns']);
        }
        $correctSyns = $word['syns'];
        shuffle($correctSyns);
        $fakeSyns = array_diff($syns, $correctSyns);
        shuffle($fakeSyns);
        $fakeSyns = array_slice($fakeSyns, 0, 4);
        $fakeSyns[] = array_pop($correctSyns);
        shuffle($fakeSyns);

        return $fakeSyns;
    }

    protected function getFakeWords()
    {
        $fakeList = [];
        $wordList = $this->getWordListFromRemaining();
        $wordList = json_decode($wordList, true);
        shuffle($wordList);
        $i = 0;
        foreach ($wordList as $word) {
            $fakeList[] = json_decode($this->getWord($word), true);
            $i++;
            if ($i > 4) {
                break;
            }
        }
        return $fakeList;
    }


    protected function putCache(array $word): bool
    {
        $key = $word['word'];
        return $this->redis->set($key, json_encode($word));
    }

    protected function getPhonetics(string $word): array|string
    {
        curl_setopt($this->curl, CURLOPT_URL, $this->phoneticsUrl . $word); // 不包括头部信息
        $response = $this->getResponse();
        $response = json_decode($response, true);
        $response = $response[0] ?? '';
        $phonetics = $response['phonetics'] ?? '';
        return $phonetics ? $this->parsePhonetics($phonetics) : '';
    }

    protected function getThesaurus(string $word): array
    {

        curl_setopt($this->curl, CURLOPT_URL, $this->thesaurusUrl . $word . $this->thesaurusKey); // 不包括头部信息
        $response = $this->getResponse();
        $response = json_decode($response, true)[0];

        return [
            "syns" => $response['meta']['syns'][0] ?? '',
            "ants" => $response['meta']['ants'][0] ?? '',
        ];
    }

    protected function getDictionary(string $word): array
    {
        curl_setopt($this->curl, CURLOPT_URL, $this->dictionaryUrl . $word . $this->dictionaryKey); // 不包括头部信息
        $response = $this->getResponse();
        $response = json_decode($response, true)[0];
        return [
            "definition" => $response['shortdef'] ?? '',
        ];
    }




    protected function parsePhonetics(array $phonetics): array
    {
        $result = [];
        foreach ($phonetics as $phonetic) {
            if (
                key_exists("audio", $phonetic) && $phonetic['audio'] != "" &&
                key_exists("text", $phonetic) && $phonetic['text'] != ""
            ) {
                $fileUrl = explode("/", $phonetic['audio']);
                $file = $fileUrl[count($fileUrl) - 1];
                $url = $phonetic['audio'];
                $text = $phonetic['text'];
                $this->getAudioFile($url, $file);
                $result[] = [
                    "audio" => $file,
                    "text" => $text,
                ];
            }
        }
        return $result;
    }



    protected function getAudioFile(string $url, string $file): bool
    {
        if (file_exists($this->audioFolder . $file)) {
            return true;
        }

        curl_setopt($this->curl, CURLOPT_URL, $url); // 不包括头部信息
        return $this->getResponse(true, $this->audioFolder . $file);
    }

    protected function getResponse(bool $isFile = false, ?string $filePath = null): bool|string
    {
        $response = curl_exec($this->curl);


        // 检查请求是否成功
        if (curl_errno($this->curl)) {
            // 如果发生错误，显示错误信息
            return false;
        } else {
            if ($isFile) {
                // 保存文件
                echo $filePath . PHP_EOL;
                file_put_contents($filePath, $response);
                return true;
            }
        }
        return $response;
    }

    protected function wordExist(string $word): bool|string
    {
        return $this->redis->get($word);
    }


    public function init()
    {
        $fileHandle = fopen($this->fullList, 'r');

        // Check if the file was opened successfully
        if ($fileHandle) {
            // Read the file line by line
            while (($line = fgets($fileHandle)) !== false) {
                // Remove any extra whitespace, including new lines
                $line = trim($line);
                echo "<h1>$line</h1>";
                // Process the line (e.g., print it)
                $word = $this->getWord($line);
                // echo "<p>";
                // echo  json_encode($word);
                // echo "</p>";
            }

            // Close the file
            fclose($fileHandle);
        } else {
            // Handle error opening the file
            echo "Error: Unable to open the file.";
        }
    }


    public function getProgress()
    {
        return $this->checkProgress();
    }

    protected function checkProgress(): float
    {
        if (!$this->redis->get($this->remainingList) || !$this->redis->get($this->workingInProgress)) {
            $this->initProgressFiles();
        }

        $this->checkProgressIsEmpty();

        $completed = $this->redis->get($this->completedList);
        $completed = json_decode($completed, true) ?? [];
        $remaining = $this->redis->get($this->remainingList);
        $remaining = json_decode($remaining, true);
        $total = (count($completed) + count($remaining));

        return (count($completed) / $total) * 100;
    }

    protected function checkProgressIsEmpty()
    {
        $progressList = json_decode($this->redis->get($this->workingInProgress), true);
        if(count($progressList) == 0) {
            $this->makeNewProgressList();
        }
    }

    protected function makeNewProgressList()
    {

        $remainList = json_decode($this->redis->get($this->remainingList));

        $shuffledList = $remainList;
        shuffle($shuffledList);

        $workingInProgress = array_slice($shuffledList, 0, $this->defaultProgressDaily);

        $this->redis->set($this->workingInProgress, json_encode($workingInProgress));

        $remaining = array_slice($shuffledList, $this->defaultProgressDaily);
        $this->redis->set($this->remainingList, json_encode($remaining));

    }



    protected function initProgressFiles()
    {
        $fullList = file($this->fullList, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        $shuffledList = $fullList;
        shuffle($shuffledList);
        $workingInProgress = array_slice($shuffledList, 0, $this->defaultProgressDaily);

        $this->redis->set($this->workingInProgress, json_encode($workingInProgress));

        $remaining = array_slice($shuffledList, $this->defaultProgressDaily);
        $this->redis->set($this->remainingList, json_encode($remaining));
    }

    public function getWordListFromWorkingInProgress()
    {
        return $this->redis->get($this->workingInProgress);
    }

    public function getWordListFromRemaining()
    {
        return $this->redis->get($this->remainingList);
    }

    public function checkAnswer($word, $answer): string
    {
        $res = "Wrong";
        $wordDef = json_decode($this->redis->get($word), true);

        $result = in_array($answer, $wordDef['syns']);

        if ($result) {
            $this->moveToCompletedList($word);
            $this->removeFromWorkingInProgressList($word);
            $res = "Correct";
        }

        return json_encode([$res]);
    }

    protected function moveToCompletedList($word)
    {
        $completedList = json_decode($this->redis->get($this->completedList), true);
        $completedList[] = $word;
        $this->redis->set($this->completedList, json_encode($completedList));
    }

    protected function removeFromWorkingInProgressList($word)
    {
        $workingList = json_decode($this->redis->get($this->workingInProgress), true);
        $index = array_search($word, $workingList);

        if ($index !== false) {
            unset($workingList[$index]);
        }

        $workingList = array_values($workingList);
        $this->redis->set($this->workingInProgress, json_encode($workingList));
    }

    public function reset()
    {
        $completedList = json_decode($this->redis->get($this->completedList), true);
        $progressList =  json_decode($this->redis->get($this->workingInProgress), true);

        $progressList = array_merge($progressList, $completedList);
        $this->redis->set($this->completedList, json_encode([]));
        $this->redis->set($this->workingInProgress, json_encode($progressList));
    }

    public function manualRest()
    {
        $progressList = ["shrink", "grate", "wasteful", "mogul", "benign", "Dismay", "Askance", "comical", "inert", "hypothetical", "ratify", "aberration", "opaque", "condemn", "Reprieve", "becoming", "suspend", "gauge", "interpret", "sane", "gung-ho", "Introvert", "whimsical", "bolster", "Pillage", "Pillage", "convert", "untoward", "masculinity", "unworthy", "predecessor", "tardy", "sanctuary", "dull", "pliant", "Frivolous"];
        $this->redis->set($this->workingInProgress, json_encode($progressList));
    }



    public function __destruct()
    {
        // 关闭 cURL 会话
        curl_close($this->curl);
    }
}
