import requests 
import json
from dictionary import dictionary

dictionary = dictionary()

def getAudioData(phonetics):
    audioData = []
    for phonetic in phonetics:
        audio = {}
        if "audio" in phonetic:
            audioURL = phonetic["audio"]
            filePath = audioURL.split('/')
            file = filePath[-1]
            if file:
                audio["audio"] = file
            if audioURL:
                audio["url"] = audioURL
        
        if "text" in phonetic:
            audio['text'] = phonetic['text']

        keyRequired = ["text", "audio", "url"]
        # if all(key in audio for key in keyRequired):
        #     audioData.append(audio)
        audioData.append(audio)
    return audioData

response = {}

url = "https://api.dictionaryapi.dev/api/v2/entries/en/"

data = requests.get(url+"hello")

data = json.loads(data.text)
data = data[0]

response["word"] = data["word"]

phoneticsData = getAudioData(data["phonetics"])

for phonetic in phoneticsData:
    response["audios"]=(dictionary.getPhoneticsFile(phonetic["url"], phonetic["audio"]))

print(response)

