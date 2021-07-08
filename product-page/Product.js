const Product = (function() {

    const Item = (function() {
        const protectedItem = new WeakMap();

        class Item {
            constructor(item) {
                protectedItem.set(this, {
                    id: item.ID,
                    article: item.ARTICLE,
                    name: item.NAME
                });
            }

            get Id() { return protectedItem.get(this).id }

            get Article() { return protectedItem.get(this).article }

            get Name() { return protectedItem.get(this).name }
        }

        return Item;
    })();

    const Offer = (function() {

        class Offer {
            constructor(item) {
                this._id = item.ID;
                this._characteristic = item.CHARACTERISTIC;
                this._data = item.PPDATA;
                this._price = Number.parseFloat(item.PRICE).toFixed(2);
                this._residue = Number.isInteger(item.RESIDUE) ? item.RESIDUE : Number.parseInt(item.RESIDUE);
            }

            get Id() { return this._id }

            get Characteristic() { return this._characteristic }

            get Data() { return this._data }

            get Price() { return this._price }

            get Residue() { return this._residue }
        }

        return Offer;
    })();

    const protectedFields = new WeakMap();
    const offers = new Array();
    const founds = new Array();

    class Product {

        constructor(item) {

            while (offers.length > 0) {
                offers.pop();
            }

            while (founds.length > 0) {
                founds.pop();
            }

            protectedFields.set(this, {
                id: item.PRODUCT.ID,
                price: Number.parseFloat(item.PRODUCT.PRICE).toFixed(2),
                name: item.PRODUCT.NAME,
                description: item.PRODUCT.DETAIL_TEXT,
                article: item.PRODUCT.ARTICLE,
                status: item.PRODUCT.STATUS,
                valume: Number.parseFloat(item.PRODUCT.VALUME).toFixed(3),
                weight: Number.parseFloat(item.PRODUCT.WEIGHT).toFixed(3)
            });

            if (item.OFFERS) {
                item.OFFERS.forEach(function(item) {
                    if (item.PRICE == '' || item.PRICE == 0) {
                        return false;
                    } else {
                        offers.push(new Offer(item));
                    }
                });
            }

            if (item.FOUND) {
                item.FOUND.forEach(function(item) {
                    founds.push(new Item(item));
                });
            }

            this._characteristics = item.PRODUCT.CHARACTERISTICS;
            this._images = item.IMAGES;
            this._protect = item.PROTECT;
        }

        get Id() { return protectedFields.get(this).id }

        get Price() { return protectedFields.get(this).price }

        get Name() { return protectedFields.get(this).name }

        get Description() { return protectedFields.get(this).description }

        get Article() { return protectedFields.get(this).article }

        get Status() { return protectedFields.get(this).status }

        get Valume() { return protectedFields.get(this).valume }

        get Weight() { return protectedFields.get(this).weight }

        get ImagesList() { return this._images }

        get ProtectList() { return this._protect }

        get OffersList() { return offers }

        get CharacteristicsList() {
            let list = new Array();
            this._characteristics.forEach(function(item) {
                if (item.VALUE == '') {
                    return false;
                } else {
                    list.push(item);
                }
            });
            return list;
        }

        get FoundsList() {
            let found = new Array();
            founds.forEach(function(item) {
                if (item.Article == '' || item.Name == '') {
                    return false;
                } else {
                    found.push({
                        Id: item.Id,
                        Name: item.Name,
                        Article: item.Article

                    });
                }
            });

            return found;
        }

        get FoundListUnsafe() { return founds }

        getOfferById(id) {

            var offer;

            offers.forEach(function(item) {
                if (item._id == id)
                    offer = item;
            });

            if (offer) {
                return Object.assign(offer);
            } else {
                return false;
            }
        }
    }

    return Product;

})();