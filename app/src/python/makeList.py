fileA = open("listA", "r")
fileB = open("listB", "r")

list = set()

for word in fileA:
    list.add(word)

for word in fileB:
    list.add(word)

print(len(list))
list = sorted(list)

with open("fullList", "w") as file:
    for word in list:
        file.write(f"{word}")


