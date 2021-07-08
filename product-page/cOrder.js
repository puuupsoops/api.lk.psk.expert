const cOrder = (function() {

    const ItemOffer = (function() {

        const protectedItemOffer = new WeakMap();

        class ItemOffer {
            constructor(offer, product) {
                protectedItemOffer.set(this, {
                    id: offer.id,
                    price: product.getOfferById(offer.id).Price,
                    name: product.getOfferById(offer.id).Characteristic,
                    complictation: false
                });

                this._amount = offer.amount;
                this._cost = (Number.parseFloat(product.getOfferById(offer.id).Price) * Number.parseFloat(offer.amount));
            }

            get Id() { return protectedItemOffer.get(this).id }

            get Price() { return protectedItemOffer.get(this).price }

            get Name() { return protectedItemOffer.get(this).name }

            get Complictation() { return protectedItemOffer.get(this).complictation }

            get Amount() { return this._amount }

            get Cost() { return this._cost }

            increase() { Number.parseInt(this._amount) += Number.parseInt(1) }

            decrease() {
                (this._amount == 0) ? this._amount = 0: Number.parseInt(this._amount) -= Number.parseInt(1)
            }
        }

        return ItemOffer;

    })();

    function recalculateTotalCount() {
        var value = 0;

        return value;
    }

    function recalculateTotalWeight() {
        var value = 0;

        return value;
    }

    function recalculateTotalVolume() {
        var value = 0;

        return value;
    }

    function recalculateTotalPrice() {
        var value = 0;

        return value;
    }

    const protectedFields = new WeakMap();
    const itemsOfferList = new Array();

    class cOrder {
        constructor(id) {

            while (itemsOfferList.length > 0) {
                itemsOfferList.pop();
            }

            protectedFields.set(this, {
                uId: id,
                totalCount: 0,
                totalWeight: 0,
                totalVolume: 0,
                totalPrice: 0
            });
        }

        get Id() { return protectedFields.get(this).uId }

        get Count() { return protectedFields.get(this).totalCount }

        get Weight() { return protectedFields.get(this).totalWeight }

        get Valume() { return protectedFields.get(this).totalVolume }

        get Price() { return protectedFields.get(this).totalPrice }

        get OfferList() { return itemsOfferList }

        update() {

        }

        addPosition(data) {
            var offers = new Array();
            data.offers.forEach(function(item) {
                offers.push(new ItemOffer(item, data.product));
            });

            itemsOfferList.push({
                product: data.product,
                offers: offers
            });
        }

        deletePosition() {

        }
    }

    return cOrder;

})();