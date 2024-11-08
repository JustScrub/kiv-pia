import requests

HOST = 'http://localhost:8880/www'

def get_id(login="test",passw="test",exp=3600):
    response = requests.post(f'{HOST}/api.php?service=get_auth_key', json={'login': login, 'pass': passw, 'expiration': exp})
    #print(response.text)
    return response.json()['key'] if response.status_code == 200 else response.json()

def dict_in_list(l,d):
    for i in range(len(l)):
        if l[i] == d:
            return True
    return False

def article_id_in_list(articles,id):
    for a in articles:
        if a["id"] == id:
            return True
    return False

def is_subdict(sub,d):
    for k in sub:
        if k not in d or sub[k] != d[k]:
            return False
    return True