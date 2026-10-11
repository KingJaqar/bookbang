"""End-to-end checks against a disposable local MariaDB database.

Run with PHP listening at 127.0.0.1:8090 and the seeded test database at port 3311.
This script never contacts production or uses real customer/payment information.
"""
import json, re, secrets, subprocess, urllib.request, urllib.parse, urllib.error
from http.cookiejar import CookieJar
from html.parser import HTMLParser

BASE='http://127.0.0.1:8090/'
MYSQL='C:/xampp/mysql/bin/mysql.exe'
checks=[]
def sql(query):
    return subprocess.check_output([MYSQL,'--host=127.0.0.1','--port=3311','--user=root','--database=bookbang_db','-N','-B','-e',query],text=True).strip()
def ok(condition,label):
    if not condition: raise AssertionError(label)
    checks.append(label); print('PASS '+label,flush=True)
class Fields(HTMLParser):
    def __init__(self,text):
        super().__init__();self.values={};self.feed(text)
    def handle_starttag(self,tag,attrs):
        a=dict(attrs)
        if tag=='input' and a.get('type')=='hidden': self.values[a.get('name')]=a.get('value','')
class Client:
    def __init__(self): self.jar=CookieJar();self.http=urllib.request.build_opener(urllib.request.HTTPCookieProcessor(self.jar));self.token=''
    def get(self,path):return self.call(path)
    def post(self,path,data):return self.call(path,data)
    def upload(self,path,data,filename,content,mime):
        boundary='BookbangTest'+secrets.token_hex(8)
        parts=[]
        for key,value in data.items():parts.append(('--'+boundary+'\r\nContent-Disposition: form-data; name="'+key+'"\r\n\r\n'+str(value)+'\r\n').encode())
        parts.append(('--'+boundary+'\r\nContent-Disposition: form-data; name="profile_picture"; filename="'+filename+'"\r\nContent-Type: '+mime+'\r\n\r\n').encode()+content+b'\r\n')
        parts.append(('--'+boundary+'--\r\n').encode())
        request=urllib.request.Request(BASE+path,data=b''.join(parts),headers={'Content-Type':'multipart/form-data; boundary='+boundary})
        response=self.http.open(request,timeout=20);body=response.read().decode();ok('Fatal error' not in body,'Profile upload has no PHP error');return body
    def call(self,path,data=None):
        if data is not None: data=urllib.parse.urlencode(data,doseq=True).encode()
        try:r=self.http.open(BASE+path,data=data,timeout=20)
        except urllib.error.HTTPError as e:r=e
        body=r.read().decode();self.last_url=r.url;self.status=r.code
        ok(not re.search(r'(Fatal error:|Warning:|Parse error:)',body),'No PHP runtime error: '+path)
        hidden=Fields(body).values
        if hidden.get('csrf'):self.token=hidden['csrf']
        return body
    def send(self,path,**data):return self.post(path,{'csrf':self.token,**data})

suffix=secrets.token_hex(4);name='qa_'+suffix;email=name+'@example.invalid';password='LocalOnly-'+secrets.token_hex(12)
c=Client();stranger=Client()
try:
    page=c.get('ProductPage.php?q=Dune');ok('1 books found' in page and 'Dune' in page and 'The Hobbit</h3>' not in page,'Search returns only matching title')
    ok('V.S. Naipaul' in c.get('ProductPage.php?q=Naipaul'),'Search by author')
    ok('0 books found' in c.get('ProductPage.php?q=nonexistent_'+suffix),'Search empty state')
    ok('0 books found' in c.get("ProductPage.php?q=%27%20OR%201%3D1--"),'SQL-like search is literal')
    page=c.get('signup.php');ok(len(c.token)==64,'CSRF token is rendered')
    ok(c.post('add_to_cart.php',{'book_id':1}).find('form expired')>=0,'Cart rejects missing CSRF')
    data=json.loads(c.send('add_to_cart.php',book_id=999999));ok(not data['success'],'Invalid book is rejected')
    data=json.loads(c.send('add_to_cart.php',book_id=1));ok(data.get('cart_count')==1,'Guest cart persists: '+str(data))
    ok('cart-badge">1' in c.get('navbar.php'),'Guest cart badge is accurate')
    c.send('library.php',book_id=6,action='save');ok('The Hobbit' in c.get('library.php'),'Guest saved books persist')
    c.get('signup.php');page=c.send('signup.php',username=name,email=email,password=password,confirm_password=password)
    ok('Save your recovery code' in page,'Registration succeeds')
    recovery=re.search(r'<code class="bb-code">([^<]+)',page).group(1)
    uid=int(sql("SELECT user_id FROM users WHERE email='%s'"%email));ok(sql('SELECT role FROM users WHERE user_id=%d'%uid)=='customer','Signup assigns customer role')
    ok(json.loads(c.get('cart_count.php'))['count']==1,'Signup preserves guest cart')
    ok('The Hobbit' in c.get('library.php'),'Signup preserves saved books')
    ok(c.get('admin.php').find('permission')>=0 and c.status==403,'Customer cannot access admin')
    c.get('signup.php');ok('already registered' in c.send('signup.php',username=name,email=email,password=password,confirm_password=password),'Duplicate signup rejected')
    c.get('user/user.php');page=c.send('user/user.php',action='profile',first_name='QA',last_name='Tester',username=name,email=email,phone='09123456789');ok('Profile updated.' in page,'Profile update persists')
    profile=dict(csrf=c.token,action='profile',first_name='QA',last_name='Tester',username=name,email=email,phone='09123456789')
    ok('Upload a JPG' in c.upload('user/user.php',profile,'fake.jpg',b'<?php echo 1;?>','image/jpeg'),'Fake image upload rejected')
    import base64
    png=base64.b64decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+aX2kAAAAASUVORK5CYII=')
    ok('Profile updated.' in c.upload('user/user.php',profile,'avatar.png',png,'image/png'),'Valid avatar upload works')
    avatar=sql('SELECT profile_picture FROM users WHERE user_id=%d'%uid);ok(re.fullmatch(r'[a-f0-9]{32}\.png',avatar) is not None,'Avatar has safe generated filename')
    ok(len(urllib.request.urlopen(BASE+'uploads/profile_pictures/'+avatar).read())==len(png),'Uploaded avatar is served')
    page=c.send('submit_review.php',book_id=1,rating=5,review_text='Local integration review '+suffix);ok('Local integration review '+suffix in page,'Review saved and rendered')
    c.get('logout.php');c.get('login.php');ok('incorrect' in c.send('login.php',email=email,password='invalid'),'Invalid login rejected')
    c.get('ProductDescription.php?id=1');c.send('add_to_cart.php',book_id=1);c.get('login.php');page=c.send('login.php',email=email,password=password)
    ok(c.last_url.endswith('ProductPage.php'),'Email login succeeds')
    ok(json.loads(c.get('cart_count.php'))['count']==2,'Login merges duplicate guest/account carts')
    cid=int(sql('SELECT cart_id FROM carts WHERE user_id=%d AND book_id=1'%uid))
    page=c.send('cart/cart.php',action='quantity',cart_id=cid,quantity=3);ok('Subtotal ₱1,785.00' in page,'Quantity and totals update')
    stranger.get('signup.php');stranger.send('cart/cart.php',action='remove',cart_id=cid);ok(sql('SELECT quantity FROM carts WHERE cart_id=%d'%cid)=='3','Cart ownership enforced')
    sql('UPDATE books SET discount=10 WHERE book_id=1')
    page=c.get('cart/cart.php');ok('Subtotal ₱1,606.50' in page,'Discount consistent in cart')
    buy=json.loads(c.send('checkout/buy_now.php',book_id=6));ok(buy['url'].endswith('?buy=6'),'Buy Now uses explicit book selection')
    page=c.get('checkout/checkout.php?buy=6');key=Fields(page).values['request_key'];ok('The Hobbit x 1' in page and 'Total: ₱699.00' in page,'Buy Now prices only selected book')
    page=c.get('checkout/checkout.php?buy=6');ok(Fields(page).values['request_key']==key and 'The Hobbit x 1' in page,'Buy Now survives refresh')
    order=dict(request_key=key,first_name='QA',last_name='Tester',email=email,phone='09123456789',address='TEST ONLY - do not deliver',city='Test City',postal_code='1000',payment_method='card')
    ok('Only Cash on Delivery' in c.send('checkout/checkout.php?buy=6',**order),'Online payment methods rejected server-side')
    order['payment_method']='cod';order['address']='';ok('delivery address' in c.send('checkout/checkout.php?buy=6',**order),'Delivery address required')
    order['address']='TEST ONLY - do not deliver';page=c.send('checkout/checkout.php?buy=6',**order);oid=int(re.search(r'Order #(\d+)',page).group(1))
    ok('Cash on Delivery' in page and 'TEST ONLY - do not deliver' in page,'COD order and delivery details saved')
    ok(sql('SELECT total FROM transactions WHERE transaction_id=%d'%oid)=='699.00','Correct Buy Now total stored')
    ok(json.loads(c.get('cart_count.php'))['count']==3,'Buy Now preserves unrelated cart')
    page=c.get('checkout/checkout.php?buy=invalid');ok('Place COD order' not in page and c.status==404,'Invalid Buy Now cannot silently order cart')
    before=sql('SELECT COUNT(*) FROM transactions WHERE user_id=%d'%uid);c.send('checkout/checkout.php?buy=6',**order);ok(sql('SELECT COUNT(*) FROM transactions WHERE user_id=%d'%uid)==before,'Repeated submission creates no duplicate order')
    ok(stranger.get('orders/order.php?id=%d'%oid).find('Order not found')>=0 and stranger.status==404,'Order history ownership enforced')
    page=c.get('checkout/checkout.php');order['request_key']=Fields(page).values['request_key'];page=c.send('checkout/checkout.php',**order);ok('Total: ₱1,606.50' in page,'Cart COD order has discounted total')
    ok(json.loads(c.get('cart_count.php'))['count']==0,'Ordered cart items are cleared')
    page=c.get('checkout/checkout.php');ok('Place COD order' not in page,'Empty cart cannot be ordered')
    c.get('logout.php');c.get('forgot-password.php');new_password='UpdatedLocal-'+secrets.token_hex(12)
    page=c.send('forgot-password.php',email=email,recovery_code=recovery,password=new_password,confirm_password=new_password);ok('Password updated.' in page,'Recovery reset succeeds')
    ok('incorrect' in c.send('forgot-password.php',email=email,recovery_code=recovery,password=password,confirm_password=password),'Recovery code is one-time')
    c.get('login.php');page=c.send('login.php',email=name,password=new_password);ok(c.last_url.endswith('ProductPage.php'),'Username login with reset password succeeds')
    sql("UPDATE users SET role='admin' WHERE user_id=%d"%uid);page=c.get('admin.php');ok('BookBang administration' in page,'Admin dashboard works')
    admin_csrf=Fields(page).values['csrf'];c.post('admin.php',{'csrf':admin_csrf,'action':'update_order','view':'transactions','transaction_id':oid,'status':'delivered'})
    page=c.get('orders/order.php?id=%d'%oid);ok('Delivered' in page and 'Paid' in page,'Admin COD fulfilment status appears to customer')
    ok('₱699.00' in c.get('admin.php'),'Admin reports collected COD revenue')
    c.post('admin.php',dict(csrf=admin_csrf,action='update_order',view='transactions',transaction_id=oid,status='cancelled'))
    ok('₱0.00' in c.get('admin.php'),'Cancelled and unpaid orders excluded from collected revenue')
    book=dict(csrf=admin_csrf,action='save_book',view='books',title='QA book '+suffix,author='Test author',genre='Test genre',description='Fixture only',image='Assets/bookbanglogo.png',price='123.45',discount='10',pages='100',collection='Test')
    c.post('admin.php',book);bid=int(sql("SELECT book_id FROM books WHERE title='QA book %s'"%suffix));ok(bid>25,'Admin creates book')
    book.update(book_id=bid,price='234.56');c.post('admin.php',book);ok(sql('SELECT price FROM books WHERE book_id=%d'%bid)=='234.56','Admin updates book')
    c.post('admin.php',dict(csrf='wrong',action='save_book',view='books',**{k:v for k,v in book.items() if k not in ['csrf','action','view']}));ok(sql('SELECT price FROM books WHERE book_id=%d'%bid)=='234.56','Admin rejects invalid CSRF')
    c.post('admin.php',dict(csrf=admin_csrf,action='delete_book',view='books',book_id=1));ok(sql('SELECT COUNT(*) FROM books WHERE book_id=1')=='1','Admin preserves books in order history')
    c.post('admin.php',dict(csrf=admin_csrf,action='delete_book',view='books',book_id=bid));ok(sql('SELECT COUNT(*) FROM books WHERE book_id=%d'%bid)=='0','Admin deletes unsold book')
    customer_email='customer_'+suffix+'@example.invalid';sql("INSERT INTO users(username,email) VALUES ('customer_%s','%s')"%(suffix,customer_email));customer_id=int(sql("SELECT user_id FROM users WHERE email='%s'"%customer_email))
    ok(customer_email in c.get('admin.php?view=customers'),'Admin customer list works')
    ok('Purchase history' in c.get('admin.php?view=customers&customer=%d'%customer_id),'Admin customer history works')
    c.get('newsletter.php');page=c.send('newsletter.php',email=email,consent=1);token=re.search(r'code: ([a-f0-9]{64})',page).group(1);ok(sql("SELECT COUNT(*) FROM newsletter_subscribers WHERE email='%s'"%email)=='1','Newsletter subscription saved')
    ok(email in c.get('admin.php?view=subscribers'),'Admin sees opted-in newsletter subscribers')
    c.get('newsletter.php')
    c.send('newsletter.php',action='unsubscribe',email=email,token=token);ok(sql("SELECT COUNT(*) FROM newsletter_subscribers WHERE email='%s'"%email)=='0','Unsubscribe works')
    ok(json.loads(c.get('auth/auth_status.php'))['logged_in'],'Auth status endpoint returns valid JSON')
    c.get('user/user.php');updated='ProfileChange-'+secrets.token_hex(10);page=c.send('user/user.php',action='password',current_password=new_password,new_password=updated,confirm_password=updated);ok('Password updated.' in page,'Authenticated password change works')
    c.get('logout.php');c.get('login.php');page=c.send('login.php',email=email,password=updated);ok(c.last_url.endswith('admin.php'),'Login works after password change')
    guest=Client();guest.get('ProductDescription.php?id=2');guest.send('add_to_cart.php',book_id=2);page=guest.get('checkout/checkout.php');guest_order={**order,'request_key':Fields(page).values['request_key']};page=guest.send('checkout/checkout.php',**guest_order);gid=int(re.search(r'Order #(\d+)',page).group(1));ok(sql('SELECT user_id IS NULL FROM transactions WHERE transaction_id=%d'%gid)=='1','Guest COD order works')
    guest.get('login.php');guest.send('login.php',email=email,password=updated);ok(sql('SELECT user_id FROM transactions WHERE transaction_id=%d'%gid)==str(uid),'Guest orders merge into account on login')
    ok('Order #%d'%gid in guest.get('orders/order.php?id=%d'%gid),'Merged guest order accessible in account')
    print(json.dumps({'passed':len(checks),'result':'PASS'},indent=2))
finally:
    sql('UPDATE books SET discount=0 WHERE book_id=1')
    # Fixtures are confined to this disposable localhost database.
    sql("DELETE FROM transactions WHERE email='%s'; DELETE FROM reviews WHERE review_text='Local integration review %s'; DELETE FROM users WHERE email='%s'"%(email,suffix,email))
    if 'customer_email' in globals():sql("DELETE FROM users WHERE email='%s'"%customer_email)
    if 'avatar' in globals():
        from pathlib import Path
        (Path(__file__).resolve().parents[1]/'uploads/profile_pictures'/avatar).unlink(missing_ok=True)
