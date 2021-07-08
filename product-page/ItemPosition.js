class ItemPosition {

    constructor() {
        this._product;
        this._name;
        this._itemsOfferList = [];
    }

    findOfferByID(id) {
        let offer;

        this._itemsOfferList.forEach(function(item) {

            /*             console.group('ItemPosition: findOfferByID');
                        console.log(id);
                        console.log(item);
                        console.log(item.Id);
                        console.groupEnd(); */

            if (item.Id == id)
                offer = item;
        });

        if (!offer) {
            return false;
        } else {
            return offer;
        }
    }

    addOffer(offer) {
        this._itemsOfferList.push(new ItemOffer(offer, this._product));
    }

    deleteOffer(id) {
        let _index;
        this._itemsOfferList.forEach(function(item, index) {
            if (item.Id == id)
                _index = index;
        });

        this._itemsOfferList.splice(_index, 1);
    }

    getOffer(id) { return this.findOfferByID(id); }

    releaseOfferList() {
        while (this._itemsOfferList.length > 0) {
            this._itemsOfferList.pop();
        }
    }

    get OffersList() { return this._itemsOfferList }

    get product() { return this._product }

    get productId() { return this._product.Id }

    get totalVolume() {
        var value = 0;
        var product = this._product;

        if (product) {
            this._itemsOfferList.forEach(function(item) {

                value = value + (item.Amount * product.Valume)
            });
            return value;
        } else {
            value = 0;
            return value;
        }
    }

    get totalWeight() {
        var value = 0;
        var product = this._product;

        if (product) {
            this._itemsOfferList.forEach(function(item) {

                value = value + (item.Amount * product.Weight)
            });
            return value;
        } else {
            value = 0;
            return value;
        }

    }

    get totalPrice() {
        var value = Number.parseFloat(0);
        this._itemsOfferList.forEach(function(item) {
            value += Number.parseFloat(item.Price)
        });
        return value;
    }

    get totalAmount() {
        var value = Number.parseInt(0);
        this._itemsOfferList.forEach(function(item) {
            value += Number.parseInt(item.Amount)
        });
        return value;
    }

    get totalCost() {
        var value = 0;
        this._itemsOfferList.forEach(function(item) {
            value += (Number.parseFloat(item.Price).toFixed(2) * Number.parseFloat(item.Amount).toFixed(2))
        });
        return value;
    }

}

const itemPosition = new ItemPosition();