import requests
import os



class dictionary:

    def __init__(self) -> None:
        self.audioFolder= "./Audio/"

    def getPhoneticsFile(self, url, file):
        if not os.path.exists(self.audioFolder+file):
            response = requests.get(url)
            with open(self.audioFolder+file, 'wb') as file:
                file.write(response.content)

        print(self.audioFolder+file)
        return self.audioFolder+file

    def getMeanings(meaings):d




